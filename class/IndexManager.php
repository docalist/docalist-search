<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Search;

use Docalist\Search\Indexer;
use Docalist\Search\Indexer\MissingIndexer;
use InvalidArgumentException;
use RuntimeException;
use Exception;
use Docalist\Search\Mapping\Options;

/**
 * Gestionnaire d'index docalist-search.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class IndexManager
{
    /**
     * Nom de l'option WordPress où sont stockés les libellés des attributs de recherche.
     *
     * @var string
     */
    private const OPTION_LABELS = 'docalist-search-attributes-label';

    /**
     * Nom de l'option WordPress où sont stockés les descriptions des attributs de recherche.
     *
     * @var string
     */
    private const OPTION_DESCRIPTIONS = 'docalist-search-attributes-description';

    /**
     * Nom de l'option WordPress où sont stockés les caractéristiques des attributs de recherche.
     *
     * @var string
     */
    private const OPTION_FEATURES = 'docalist-search-attributes-features';

    /**
     * Les paramètres de docalist-search.
     *
     * @var Settings
     */
    private $settings;

    /**
     * Liste des indexeurs disponibles.
     *
     * Initialisé lors du premier appel à getAvailableIndexers().
     *
     * @var Indexer[]
     */
    private $indexers;

    /**
     * Un buffer qui accumulent les documents à envoyer au serveur ES.
     *
     * Quand on ajoute ou qu'on supprime des documents, les commandes correspondantes sont stockées
     * dans le buffer dans le format attendu par l'API "bulk" de Elastic Search :
     * https://www.elastic.co/guide/en/elasticsearch/reference/master/docs-bulk.html
     *
     * Lorsque le buffer atteint sa taille maximale, il est envoyé à elasticsearch puis réinitialisé.
     * La taille maximale du buffer est déterminée par les paramètres "bulkMaxSize" et "bulkMaxCount".
     * Le buffer est flushé dès que l'une de ces deux limites est atteinte ou bien lorsque la requête
     * se termine (appel du destructeur de cette classe).
     *
     * Il est également possible de forcer l'envoi des commandes en attente et de vider le buffer en
     * appellant la méthode flush().
     *
     * @var string
     */
    private $bulk = '';

    /**
     * La taille maximale, en octets, autorisée pour le buffer (settings.bulkMaxSize).
     *
     * @var int
     */
    private $bulkMaxSize;

    /**
     * Le nombre maximum de documents autorisés dans le buffer (settings.bulkMaxCount).
     *
     * @var int
     */
    private $bulkMaxCount;

    /**
     * Le nombre actuel de documents stockés dans le buffer.
     *
     * @var int
     */
    private $bulkCount = 0;

    /**
     * Statistiques sur la réindexation.
     *
     * @var array Un tableau de la forme type => statistiques
     *
     * cf. updateStat() pour le détail des statistiques générées pour chaque type.
     */
    private $stats = [];

    /**
     * Vrai si on utilise elasticsearch version 7 ou plus (y compris 7.0.0-alpha, etc.)
     *
     * @var bool
     */
    private $es7;

    /**
     * Les attributs de recherche disponibles.
     *
     * @var SearchAttributes|null
     */
    private $searchAttributes = null;

    /**
     * Initialise le gestionnaire d'index.
     *
     * @param Settings $settings
     */
    public function __construct(Settings $settings)
    {
        // Stocke les paramètres de l'indexeur
        $this->settings = $settings;

        // Détermine la version de elasticsearch
        $version = $this->settings->esversion->getPhpValue();
        $this->es7 = version_compare($version, '6.99', '>'); // au moins 7.0.0, 7.0-alpha, 7.0-rc2 ou plus

        // Initialise les paramètres du buffer
        $this->bulkMaxSize = (int) $this->settings->bulkMaxSize->getPhpValue() * 1024 * 1024; // Mo -> octets
        $this->bulkMaxCount = $this->settings->bulkMaxCount->getPhpValue();

        // Active l'indexation en temps réel
        if ($this->settings->realtime->getPhpValue()) {
            // On utilise l'action wp_loaded pour être sûr que tous les plugins ont installé leurs filtres.
            add_action('wp_loaded', function () {
                foreach ($this->getTypes() as $type) { // getActiveIndexers()
                    $this->getIndexer($type)->activateRealtime($this);
                }
            });
        }
    }

    /**
     * Destructeur. Flushe le buffer s'il y a des documents en attente.
     */
    public function __destruct()
    {
        $this->flush();
        // @todo: un destructeur ne doit pas générer d'exception (cf php).
    }

    /**
     * Retourne la liste des indexers disponibles.
     *
     * Lors du premier appel, le filtre 'docalist_search_get_indexers' est exécuté.
     * Les indexeurs disponibles doivent intercepter ce filtre et s'ajouter dans le tableau passé en paramètre.
     *
     * @return Indexer[] Un tableau de la forme type => Indexeur.
     */
    public function getAvailableIndexers(): array
    {
        if (is_null($this->indexers)) {
            $this->indexers = apply_filters('docalist_search_get_indexers', []);
        }

        return $this->indexers;
    }

    /**
     * Retourne l'indexeur à utiliser pour un type de contenu donné.
     *
     * @param string $type Le type de contenu.
     *
     * @return Indexer L'indexeur à utiliser pour ce type de contenu.
     *
     * @throws InvalidArgumentException Si aucun indexeur n'est disponible pour le type indiqué.
     */
    public function getIndexer(string $type): Indexer
    {
        // Garantit que la liste des indexeurs disponibles a été initialisée
        $this->getAvailableIndexers();

        // Génère une admin notice si aucun indexeur n'est disponible pour le type indiqué
        if (!isset($this->indexers[$type])) {
            docalist('admin-notices')->warning(
                sprintf(__('Warning: indexer for type "%s" is not available', 'docalist-search'), $type),
                'docalist-search' // titre de la notice
            );
            $this->indexers[$type] = new MissingIndexer($type);
        }

        // Génère une exception si ce n'est pas un Indexer
        if (! $this->indexers[$type] instanceof Indexer) {
            throw new InvalidArgumentException(sprintf(
                __('Error: invalid indexer for type "%s"', 'docalist-search'),
                $type
            ));
        }

        // Ok
        return $this->indexers[$type];
    }

    /**
     * Retourne la liste des contenus indexés.
     *
     * @return string[] Les noms des des types de contenus qui sont indexés.
     */
    public function getTypes(): array
    {
        return $this->settings->types->getPhpValue();
    }

    /**
     * Retourne la liste des indexeurs disponibles indexés par nom de collection.
     *
     * Similaire à getAvailableIndexers() sauf que les clés du tableau contiennent la nom de la collection indexée
     * au lieu du type.
     *
     * @return Indexer[] Un tableau de la forme collection collection => Indexer
     */
    public function getCollections()
    {
        $collections = [];
        foreach ($this->getAvailableIndexers() as $indexer) {
            $collections[$indexer->getCollection()] = $indexer;
        }

        return $collections;
    }

    /**
     * Retourne le Mapping obtenu en fusionnant les mappings de tous les types indexés.
     *
     * @return Mapping
     */
    private function getMapping(): Mapping
    {
        $mapping = new Mapping('_doc');
        foreach ($this->getTypes() as $type) {
            $mapping->mergeWith($this->getIndexer($type)->getMapping());
        }

        return $mapping;
    }

    /**
     * Retourne les options de mapping à utiliser pour générer les settings de l'index.
     *
     * @return Options
     */
    private function getMappingOptions(): Options
    {
        return new Options([
            Options::OPTION_VERSION => $this->settings->esversion->getPhpValue(),
            Options::OPTION_DEFAULT_ANALYZER => 'french_text', // todo : transférer databaseettings->search
            Options::OPTION_LITERAL_ANALYZER => 'text', // todo : option à ajouter ?
        ]);
    }

    /**
     * Stocke des informations sur les attributs de recherche générés par le mapping passé en paramètre.
     *
     * @param Mapping $mapping
     */
    private function storeSearchAttributes(Mapping $mapping): void
    {
        // Met à jour les options
        update_option(self::OPTION_LABELS, $mapping->getFieldsLabel(), false);
        update_option(self::OPTION_DESCRIPTIONS, $mapping->getFieldsDescription(), false);
        update_option(self::OPTION_FEATURES, $mapping->getFieldsFeatures(), false);

        // Force getSearchAttributes() à recharger les options
        $this->searchAttributes = null;
    }

    /**
     * Retourne la liste des attributs de recherche disponibles.
     *
     * @return SearchAttributes
     */
    public function getSearchAttributes(): SearchAttributes
    {
        is_null($this->searchAttributes) && $this->searchAttributes= new SearchAttributes(
            function (): array {
                return get_option(self::OPTION_LABELS, []);
            },
            function (): array {
                return get_option(self::OPTION_DESCRIPTIONS, []);
            },
            function (): array {
                return get_option(self::OPTION_FEATURES, []);
            }
        );

        return $this->searchAttributes;
    }

    /**
     * Crée l'index ElasticSearch et lance une indexation complète de tous les contenus indexés.
     */
    public function createIndex(): void
    {
        // Récupère la connexion elastic search
        $es = docalist('elasticsearch'); /** @var ElasticSearchClient $es */

        // Récupère le nom de base de l'index
        $base = $this->settings->index();

        // Crée un nom unique pour le nouvel index
        $index = $base . '-' . round(microtime(true) * 1000); // Heure courante (UTC), en millisecondes

        // Détermine les settings du nouvel index
        $mapping = $this->getMapping();
        $settings = $mapping->getIndexSettings($this->getMappingOptions());

        // Utilise le nombre de shards indiqué en config
        $settings['settings']['index']['number_of_shards'] = $this->settings->shards();

        // Pas de réplicas et pas de refresh le temps qu'on crée l'index
        $settings['settings']['index']['number_of_replicas'] = 0;
        $settings['settings']['index']['refresh_interval'] = -1;

        // ES > 7 limite le nombre de résultats à 10000
        // Augmente la limite en attendant de voir si on peut appliquer une meilleure solution (scroll, search_after)
        if ($this->es7) {
            $settings['settings']['index']['max_result_window'] = 1000000; // 1 million
        }
        do_action('docalist_search_before_create_index', $base, $index);

        // Crée le nouvel index
        $this->checkAcknowledged('creating index', $es->put('/' . $index, $settings));

        // Crée l'alias "write" (nom de base + suffixe '_write')
        $this->createAlias($base . '_write', $index); // garder synchro avec flush()

        // A partir de maintenant, toutes les écritures se font dans le nouvel index, que ce soient les nôtres ou
        // celles faites depuis une autre requête php (sauvegarde d'une notice pendant qu'on réindexe, par exemple)

        // Active l'indexation en temps réel si elle ne l'était pas encore
        if (! $this->settings->realtime()) {
            $this->settings->realtime = true;
            $this->settings->save();
        }

        // Réindexe tous les contenus
        $this->reindex();

        // Rétablit les paramétres normaux de l'index (réplicats, temps de refresh)
        // cf. https://www.elastic.co/guide/en/elasticsearch/reference/master/indices-update-settings.html
        $this->checkAcknowledged(
            'setting number_of_replicas and refresh_interval',
            $es->put('/' . $index . '/_settings', [
                'index' => [
                    'number_of_replicas' => $this->settings->replicas(),
                    'refresh_interval' => null // null = rétablir la valeur par défaut (1s)
                ]
            ])
        );

        // Force un refresh
        $es->post('/' . $index . '/_refresh'); // pas vraiment utile, il y aura un auto refresh au bout x secondes

        // Crée l'alias "read" (nom de base) et active le nouvel index pour la recherche
        do_action('docalist_search_activate_index', $base, $index);
        $this->createAlias($base, $index);

        // Stocke les attributs de recherche
        $this->storeSearchAttributes($mapping);

        // Supprime tous les anciens index
        do_action('docalist_search_remove_old_indices');
        $this->deleteOldIndices($base, $index);

        // Terminé
        do_action('docalist_search_after_create_index');
    }

    /**
     * Supprime tous les index de la forme $baseName-* sauf celui indiqué dans $indexToKeep.
     *
     * @param string $baseName      Nom de base des index à supprimer (un tiret de fin est ajouté automatiquement).
     * @param string $indexToKeep   Index à conserver.
     */
    private function deleteOldIndices(string $baseName, string $indexToKeep): void
    {
        // Récupère la connexion elastic search
        $es = docalist('elasticsearch'); /* @var ElasticSearchClient $es */

        // Récupère la liste de tous les index qui existent
        // Au lieu d'utiliser le endpoint _settings qui renvoie trop d'informations, on utilise /_alias
        // qui nous retourne la liste des index et pour chaque index la liste de ses alias.
        $indices = $es->get('/_alias/');

        // On obtient un objet de la forme :
        // {
        //     "wp_prisme_index1": {
        //         "aliases": {
        //             "wp_prisme": {},
        //             "wp_prisme_write": {}
        //         }
        //     },
        //     "autre_index": {
        //         "aliases": {}
        //     }
        // }

        // On ne veut que les clés
        $indices = array_keys((array) $indices);

        // Détermine la liste des index à supprimer
        $delete = [];
        $baseName .= '-'; // les alias sont de la forme basename-datetime
        foreach ($indices as $index) {
            // Si c'est l'index qu'on veut garder, continue
            if ($index === $indexToKeep) {
                continue;
            }

            // Si ce n'est pas un de nos index, continue
            if (0 !== strncmp($index, $baseName, strlen($baseName))) {
                continue;
            }

            // Ok, c'est un index à supprimer
            $delete[] = $index;
        }

        // S'il n'y a aucun index à supprimer, terminé
        if (empty($delete)) {
            return;
        }

        // Supprime tous les index trouvés
        // cf. https://www.elastic.co/guide/en/elasticsearch/reference/current/multi-index.html
        // cf. https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-delete-index.html
        $this->checkAcknowledged('deleting old indices', $es->delete('/' . implode(',', $delete)));
    }

    /**
     * Crée un alias.
     *
     * L'alias est supprimé s'il existait déjà et il est recréé pour pointer sur l'index
     * indiqué (l'opération est atomique).
     *
     * @param string    $alias  Nom de l'alias.
     * @param string    $index  Nom de l'index sur lequel doit pointer l'alias.
     */
    private function createAlias(string $alias, string $index): void
    {
        // Récupère la connexion elastic search
        $es = docalist('elasticsearch'); /* @var ElasticSearchClient $es */

        $request = [
            'actions' => [
                ['remove'   => ['alias' => $alias, 'index' => '*'    ]],
                ['add'      => ['alias' => $alias, 'index' => $index ]],
            ]
        ];

        $this->checkAcknowledged('creating alias ' . $alias, $es->post('/_aliases', $request));
    }

    /**
     * Ajoute ou met à jour un document dans l'index.
     *
     * Si le document indiqué existe déjà dans l'index elasticsearch, il est mis à jour, sinon il est créé.
     *
     * Il n'y a pas d'attribution automatique d'ID : vous devez fournir l'ID du document à indexer.
     *
     * @param string    $type       Type du document.
     * @param int       $id         ID du document.
     * @param array     $document   Les données à ajouter dans l'index.
     */
    public function index(string $type, int $id, array $document): void
    {
        // Format d'une commande "bulk index"
        static $format;

        // Initialise le format de la commande bulk au premier appel
        if (empty($format)) {
            // avant es7, la première ligne doit indiquer le type
            $format = $this->es7 ? '{"index":{"_id":%d}}' : '{"index":{"_id":%d,"_type":"_doc"}}';

            // la seconde ligne de la commande bulk contient le document à indexer
            $format .= "\n%s\n";
        }

        // Flushe le buffer si nécessaire
        $this->maybeFlush();

        // Stocke la commande dans le buffer
        $json = json_encode($document, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            var_dump($document);
            var_dump(json_last_error_msg());
            die('JSON error');
        }
        $this->bulk .= sprintf($format, $id, $json);
        ++$this->bulkCount;

        // Met à jour les statistiques sur la taille des documents
        $size = strlen($json);
        $this->updateStat($type, 'index', 1);
        $this->updateStat($type, 'size', $size);
    }

    /**
     * Supprime un document de l'index.
     *
     * Aucune erreur n'est générée si le document indiqué n'existe pas dans l'index Elastic Search.
     *
     * @param string    $type   Type du document.
     * @param int       $id     ID du document.
     */
    public function delete(string $type, int $id): void
    {
        // Format d'une commande "bulk delete"
        static $format;

        // Initialise le format de la commande bulk au premier appel
        if (empty($format)) {
            // avant es7, la première ligne doit indiquer le type
            $format = $this->es7 ? '{"delete":{"_id":%d}}' : '{"delete":{"_id":%d,"_type":"_doc"}}';

            // pas de seconde ligne pour une actio delete
            $format .= "\n";
        }

        // Flushe le buffer si nécessaire
        $this->maybeFlush();

        // Stocke la commande dans le buffer
        $this->bulk .= sprintf($format, $id);
        ++$this->bulkCount;
    }

    /**
     * Flushe le buffer si c'est nécessaire (si les limites sont atteintes).
     */
    private function maybeFlush(): void
    {
        if ($this->bulkCount < $this->bulkMaxCount && strlen($this->bulk) < $this->bulkMaxSize) {
            return;
        }

        $this->flush();
    }

    /**
     * Flushe le buffer.
     *
     * @action "docalist_search_before_flush(count,size)" déclenchée avant que le flush ne commence.
     * @action "docalist_search_after_flush(count,size)" déclenchée une fois le flush terminé.
     */
    public function flush(): void
    {
        // Regarde si on a des commandes en attente
        if (0 === $this->bulkCount) {
            return;
        }

        // Détermine la taille actuelle du buffer
        $count = $this->bulkCount;
        $size = strlen($this->bulk);

        // Réinitialise périodiquement le cache WordPress
        if ($count >= $this->bulkMaxCount || $size >= $this->bulkMaxSize) {
            wp_cache_init();
            // Tous les dropins "object-cache" de WordPress consomment de la mémoire (cache interne le temps de la
            // requête pour éviter d'appeller le backend, stats sur le nombre de hits, etc.) Pour une requête normale
            // ça ne pose pas de problème, mais lors d'une indexation complète, on finit par consommer toute la
            // mémoire php disponible. Pour éviter ça, on réinitialise périodiquement le cache.
        }

        // Informe qu'on va flusher
        do_action('docalist_search_before_flush', $count, $size);

        // Envoie le buffer à ES
        $time = microtime(true);
        $alias = $this->settings->index() . '_write'; // garder synchro avec createIndex()
        $result = docalist('elasticsearch')->bulk('/' . $alias . '/_bulk', $this->bulk);
        $time = microtime(true) - $time;
        // @todo : permettre un timeout plus long pour les requêtes bulk
        // @todo si erreur, réessayer ? (par exemple avec un timeout plus long)

        // Informe qu'on a flushé
        do_action('docalist_search_after_flush', $count, $size, $time);

        // La réponse retournée pour une commande bulk a le format suivant :
        // {
        //     "took": 5,
        //     "items" :
        //     [
        //         { "index":{"_index": "wordpress", "_type": "page", "_id": "85", "_version": 24, "ok": true } },
        //         { "index":{"_index": "wordpress", "_type": "post", "_id": "96", "_version": 1, "ok": true } }
        //     ]
        // }

        if (is_object($result) && isset($result->items)) {
            foreach ($result->items as $item) {
                if (isset($item->index)) {
                    $item = $item->index;

                    if (! isset($item->_version)) {
                        printf(
                            "<p style='color:red'>ElasticSearch error while indexing:<pre>%s</pre></p>",
                            json_encode($item, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
                        );
                    }
                } elseif (isset($item->delete)) {
                    // ok
                } else {
                    printf(
                        "<p style='color:red'>Unknown bulk response type:<pre>%s</pre></p>",
                        json_encode($item, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
                    );
                }
            }
        } elseif (is_object($result) && isset($result->error)) {
            printf(
                "<p style='color:red'>Bulk error:<pre>%s</pre></p>",
                json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            );
        } else {
            printf(
                "<p style='color:red'>Unknown bulk response:<pre>%s</pre></p>",
                json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            );
        }

        // Réinitialise le buffer
        $this->bulk = '';
        $this->bulkCount = 0;
    }

    /**
     * Réindexe un ou plusieurs types de contenus.
     *
     * La méthode flush() est appellée après chaque type.
     *
     * @param string|array|null $types un type, une liste de types ou null pour réindexer tous les types indexés.
     *
     * @action "docalist_search_before_reindex(types)" déclenchée avant que la réindexation ne démarre.
     * Reçoit en paramètre un tableau $types contenant la liste des types qui vont être réindexés.
     *
     * @action "docalist_search_before_reindex_type(type,label)" déclenchée quand la réindexation d'un type commence.
     *
     * @action "docalist_search_reindex_$type" déclenchée pour réindexer les documents du type $type.
     * L'indexeur qui gère ce type doit parcourir sa collection de documents et appeller la méthode index() pour
     * chaque document.
     *
     * @action "docalist_search_after_reindex_type(type,label,stats)" déclenchée quand la réindexation d'un type
     * est terminée.
     *
     * @action "docalist_search_after_reindex(types,stats)" : déclenchée quand la réindexation est terminée.
     *
     * @return self
     */
    private function reindex(): void
    {
        // Vérifie que les types indiqués sont indexés et récupère leurs libellés
        $types = $this->getTypes();
        $temp = [];
        foreach ($types as $type) {
            $temp[$type] = $this->getIndexer($type)->getLabel();
        }
        $types = $temp;

        // Informe qu'on va commencer une réindexation
        do_action('docalist_search_before_reindex', $types);

        // Vide le buffer (au cas où) pour que les stats soient justes
        $this->flush();

        // Permet au script de s'exécuter aussi longtemps que nécessaire
        set_time_limit(3600);
        ignore_user_abort(true);

        // Réindexe chacun des types demandé
        foreach ($types as $type => $label) {
            // Démarre le chronomètre et stocke l'heure de début dans les stats
            $startTime = microtime(true);

            // Informe qu'on va réindexer $type
            do_action('docalist_search_before_reindex_type', $type, $label);

            // Demande à l'indexeur de réindexer tous les contenus qu'il gère
            $this->getIndexer($type)->indexAll($this);

            // Vide le buffer
            $this->flush();

            // Met à jour les statistiques sur le temps écoulé
            $this->updateStat($type, 'time', microtime(true) - $startTime);

            // Informe qu'on a terminé la réindexation de $type
            do_action('docalist_search_after_reindex_type', $type, $label, $this->stats[$type]);
        }

        // Informe que la réindexation de tous les types est terminée
        do_action('docalist_search_after_reindex', $types, $this->stats);
    }

    /**
     * Vérifie que la réponse passée en paramètre est un objet "acknowledged:true".
     *
     * @param string $message Message à afficher si la réponse n'a pas le format attendu.
     * @param mixed $response Réponse à tester.
     *
     * @return self
     */
    private function checkAcknowledged(string $message, $response): void
    {
        if (is_object($response) && isset($response->acknowledged) && $response->acknowledged === true) {
            return;
        }

        throw new RuntimeException(sprintf(
            'Error while %s, expected {"acknowledged": true}, got %s',
            $message,
            json_encode($response)
        ));
    }

    /**
     * Met à jour une statistique.
     *
     * @param string    $type       Le type concerné.
     * @param string    $stat       La statistique à mettre à jour.
     * @param int|float $increment  L'incrément qui sera ajouté à la statistique.
     */
    private function updateStat(string $type, string $stat, $increment): void
    {
        if (! isset($this->stats[$type])) {
            $this->stats[$type] = [
                'index' => 0,       // Nombre de documents indexés
                'size' => 0,        // Taille totale de la version json des documents indexés
                'time' => 0,        // Durée de la réindexation (en secondes)
            ];
        }

        $this->stats[$type][$stat] += $increment;
    }

    /**
     * Ping le serveur ElasticSearch pour déterminer si le serveur répond et tester si l'index existe.
     *
     * @return int Retourne un chiffre qui indique le statut du serveur :
     *
     * - 0 : le serveur ne répond pas : l'url indiquée dans les paramètres
     *   est invalide ou le service n'est pas démarré.
     *
     * - 1 : le serveur répond (l'url est valide), mais l'index indiqué dans
     *   les paramètres n'existe pas encore.
     *
     * - 2 : le serveur répond (l'url est valide), et l'index indiqué dans
     *   les paramètres existe.
     */
    public function ping()
    {
        // Récupère la connexion elastic search
        $es = docalist('elasticsearch'); /* @var ElasticSearchClient $es */

        try {
            $status = $es->get('/{index}');
        } catch (Exception $e) {
            return 0;   // Le serveur ne répond pas
        }
        return 2;
        switch ($status) {
            case 404:
                return 1; // Le serveur répond mais l'index n'existe pas
            case 200:
                return 2; // Le serveur répond et l'index existe
            default:
                throw new RuntimeException('Unknown ping status "' . $status . '"');
        }
    }
}
