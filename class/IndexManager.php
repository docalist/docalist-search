<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search;

use Docalist\Search\Indexer;
use Docalist\Search\Indexer\NullIndexer;
use Psr\Log\LoggerInterface;
use InvalidArgumentException;
use RuntimeException;
use Exception;

/**
 * Gestionnaire d'index docalist-search.
 */
class IndexManager
{
    /**
     * Les paramètres de docalist-search.
     *
     * @var Settings
     */
    protected $settings;

    /**
     * Le logger à utiliser.
     *
     * @var LoggerInterface
     */
    protected $log;

    /**
     * Liste des indexeurs disponibles.
     *
     * Initialisé lors du premier appel à getAvailableIndexers().
     *
     * @var Indexer[]
     */
    protected $indexers;

    /**
     * Un buffer qui accumulent les documents à envoyer au serveur ES.
     *
     * Quand on ajoute ou qu'on supprime des documents, les "commandes" correspondantes sont stockées dans le buffer.
     * A la fin de la requête, ou bien lorsque le buffer atteint sa taille maximale, le buffer est envoyé au server
     * ES puis il est réinitialisé.
     *
     * Dans le buffer, les commandes sont stockées en JSON, dans le format attendu par l'API "bulk" de Elastic Search
     * (https://www.elastic.co/guide/en/elasticsearch/reference/master/docs-bulk.html).
     *
     * Exemple :
     *
     * <code>
     *     {"index":{"_type": "dbbasedoc", "_id":1234}}\n      // indexer cette notice
     *     {"ref":12,"title":"test1", etc.}\n
     *     {"delete":{"_type": "post", "_id":13}}\n         // supprimer cet article
     *     {"index":{"_type": "dbwebs", "_id":5678}}\n // indexer cette ressource
     *     {"ref":25,"title":"test2", etc.}\n
     *     etc.
     * </code>
     *
     * La taille maximale du buffer est déterminée par les paramètres "bulkMaxSize" et "bulkMaxCount".
     * Le buffer est flushé dès que l'une de ces deux limites est atteinte ou bien lorsque la requête se termine
     * (appel du destructeur de cette classe). Il est également possible de forcer l'envoi des commandes en attente
     * et de vider le buffer en appellant la méthode flush().
     *
     * @var string
     */
    protected $bulk = '';

    /**
     * La taille maximale, en octets, autorisée pour le buffer (settings.bulkMaxSize).
     *
     * @var int
     */
    protected $bulkMaxSize;

    /**
     * Le nombre maximum de documents autorisés dans le buffer (settings.bulkMaxCount).
     *
     * @var int
     */
    protected $bulkMaxCount;

    /**
     * Le nombre actuel de documents stockés dans le buffer.
     *
     * @var int
     */
    protected $bulkCount = 0;

    /**
     * Statistiques sur la réindexation.
     *
     * @var array Un tableau de la forme type => statistiques
     *
     * cf. updateStat() pour le détail des statistiques générées pour chaque type.
     */
    protected $stats = [];

    /**
     * Traduit un type ElasticSearch (_type) en nom de type tel que vu par l'utilisateur.
     *
     * Souvent, le type ES et le type utilisateur sont les mêmes (post, page...) mais pour une base doc, ce n'est
     * pas le cas : le type utilisateur sera (par exemple) "dbprisme" alors que les types ES indiqueront le type
     * de notice ("dbprisme-article" par exemple).
     *
     * Ce tableau est un mapping entre les types ES et le type utilisateur correspondant
     * (par exemple "dbprisme-article" => "dbprisme" avec l'exemple ci-dessus).
     *
     * Le tableau est initialisé au fil de l'eau dans index() et remove() et il est utilisé dans flush() pour
     * stocker les statistiques de réindexation dans le bon type.
     *
     * @var string[]
     */
    protected $esType = [];

    /**
     * Construit un nouvel indexeur.
     *
     * @param Settings $settings
     */
    public function __construct(Settings $settings)
    {
        // Stocke les paramètres de l'indexeur
        $this->settings = $settings;

        // Récupère le logger à utiliser
        $this->log = docalist('logs')->get('indexer');

        // Initialise les paramètres du buffer
        $this->bulkMaxSize = $this->settings->bulkMaxSize() * 1024 * 1024; // en Mo dans la config
        $this->bulkMaxCount = $this->settings->bulkMaxCount();

        // Active l'indexation en temps réel
        if ($this->settings->realtime()) {
            // On utilise l'action wp_loaded pour être sûr que tous les plugins ont installé leurs filtres.
            add_action('wp_loaded', function () {
                foreach ($this->settings->types() as $type) { // getActiveIndexers()
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
    public function getAvailableIndexers()
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
    public function getIndexer($type)
    {
        // Garantit que la liste des indexeurs disponibles a été initialisée
        $this->getAvailableIndexers();

        // Génère un message d'erreur si aucun indexeur n'est disponible pour le type indiqué
        if (!isset($this->indexers[$type])) {
            docalist('admin-notices')->warning("Warning: indexer for type '$type' is not available", 'docalist-search');
            $this->indexers[$type] = new NullIndexer();
        }

        // Génère un message d'erreur si ce n'est pas un Indexer
        if (! $this->indexers[$type] instanceof Indexer) {
            docalist('admin-notices')->error("Error: invalid indexer for type '$type'", 'docalist-search');
            $this->indexers[$type] = new NullIndexer();
        }

        // Ok
        return $this->indexers[$type];
    }

    /**
     * Retourne la liste des contenus indexés.
     *
     * @return string[] Les noms des des types de contenus qui sont indexés.
     */
    public function getTypes()
    {
        return $this->settings->types();
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
     * Construit les settings complets de l'index.
     *
     * Les settings contiennent tous les paramètres de l'index : option de configuration, analyseurs, mappings
     * des différents types, etc.
     *
     * Ils sont générés :
     * - en partant des fichiers qui figurent dans le répertoire /index-settings
     * - en ajoutant dans les settings les paramétres qui figure dans la config de docalist-search
     * - en appellant la méthode buildIndexSettings() pour chacun des indexeurs activés.
     * - en exécutant le filtre 'docalist_search_get_index_settings' sur le résultat obtenu.
     *
     * @return array
     */
    public function getIndexSettings()
    {
        // Crée le settings de base
        $settings = require __DIR__ . '/../index-settings/default.php';

        // Ajoute les paramétres qui figure dans la config de docalist-search
        $settings['settings']['index']['number_of_shards'] = $this->settings->shards();
        $settings['settings']['index']['number_of_replicas'] = $this->settings->replicas();

        // Appelle la méthode buildIndexSettings() pour chacun des indexeurs actifs
        foreach ($this->settings->types() as $type) {
            $settings = $this->getIndexer($type)->buildIndexSettings($settings);
        }

        // Permet au site, au thème ou à d'autres plugins de modifier les settings générés
        $settings = apply_filters('docalist_search_get_index_settings', $settings);

        // Ok
        return $settings;
    }

    /**
     * Crée l'index ElasticSearch et lance une indexation complète de tous les contenus indexés.
     *
     */
    public function createIndex()
    {
        // Récupère la connexion elastic search
        $es = docalist('elastic-search'); /** @var ElasticSearchClient $es */

        // Récupère le nom de base de l'index
        $base = $this->settings->index();

        // Teste s'il existe déjà un index (on teste si l'alias existe, ce qui revient au même)
        // $exists = $es->exists("/$base");

        // Crée un nom unique pour le nouvel index
        $index = $base . '-' . round(microtime(true) * 1000); // Heure courante (UTC), en millisecondes

        // Détermine les settings du nouvel index
        $settings = $this->getIndexSettings();
        do_action('docalist_search_before_create_index', $index, $settings);

        // Optimise les settings le temps qu'on créer l'index
        $replicas = $settings['settings']['index']['number_of_replicas'];
        $refresh = $settings['settings']['index']['refresh_interval'];
        $settings['settings']['index']['number_of_replicas'] = 0;
        $settings['settings']['index']['refresh_interval'] = -1;

        // Crée le nouvel index
        $this->checkAcknowledged('creating index', $es->put("/$index", $settings));

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
        $this->reindex($this->settings->types());

        // Rétablit les paramétres normaux de l'index (réplicats, temps de refresh)
        // cf. https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-update-settings.html
        $this->checkAcknowledged(
            'setting number_of_replicas and refresh_interval',
            $es->put("/$index/_settings", [
                'index' => [
                    'number_of_replicas' => $replicas,
                    'refresh_interval' => $refresh
                ]
            ])
        );

        // Force un refresh
        $es->post("/$index/_refresh"); // pas vraiment utile, il y aura un auto refresh au bout x secondes

        // Faut-il appeller force_merge (optimize) ?
        // https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-forcemerge.html

        // Crée l'alias "read" (nom de base) et active le nouvel index pour la recherche
        do_action('docalist_search_activate_index', $base, $index);
        $this->createAlias($base, $index);

        // Supprime tous les anciens index
        // cf. https://www.elastic.co/guide/en/elasticsearch/reference/current/multi-index.html
        // cf. https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-delete-index.html
        // Remarque : ignore_unavailable=true non nécessaire car on a au moins 1 index (celui qu'on vient de créer)
        do_action('docalist_search_remove_old_indices');
        $this->checkAcknowledged('deleting old indices', $es->delete("/$base-*,-$index"));

        // Terminé
        do_action('docalist_search_after_create_index');
    }

    /**
     * Crée un alias.
     *
     * L'alias est supprimé s'il existait déjà et il est recréé pour pointer sur le nouvel index passé en paramètre.
     * L'opération est atomique.
     *
     * @param string $alias Nom de l'alias.
     * @param string $index Nom du nouvel index.
     */
    protected function createAlias($alias, $index)
    {
        // Récupère la connexion elastic search
        $es = docalist('elastic-search'); /** @var ElasticSearchClient $es */

        $request = [
            'actions' => [
                ['remove'   => ['alias' => $alias, 'index' => '*'    ]],
                ['add'      => ['alias' => $alias, 'index' => $index ]]
            ]
        ];

        $this->checkAcknowledged("creating alias $alias", $es->post('/_aliases', $request));

        return $this;
    }

    /**
     * Ajoute ou met à jour un document dans l'index.
     *
     * Si le document indiqué existe déjà dans l'index Elastic Search, il est mis à jour, sinon il est créé.
     *
     * Il n'y a pas d'attribution automatique d'ID : vous devez fournir l'ID du document à indexer.
     *
     * @param string $type Le type du document.
     * @param scalar $id L'identifiant du document.
     * @param array $document Les données du document.
     * @param string $esType Nom du mapping ElasticSearch à utiliser si différent de $type.
     */
    public function index($type, $id, array $document, $esType = null)
    {
        // Format d'une commande "bulk index" pour ES
        static $format = "{\"index\":{\"_type\":%s,\"_id\":%s}}\n%s\n";

        // Vérifie le type et l'id
        $this->checkType($type)->checkId($id);

        // esType sert à initialiser _type, par défaut est égal à type, différent pour un Database
        is_null($esType) && $esType = $type;
        $this->esType[$esType] = $type;

        $this->log && $this->log->info('index({type},{id})', [
            'type' => $type,
            '_type' => $esType,
            'id' => $id,
            'document' => $document
        ]);

        // Flushe le buffer si nécessaire
        $this->maybeFlush();

        // Stocke la commande dans le buffer
        $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        $document = json_encode($document, $options);
        $this->bulk .= sprintf($format, json_encode($esType, $options), json_encode($id, $options), $document);
        ++$this->bulkCount;

        // Met à jour les statistiques sur la taille des documents
        $size = strlen($document);
        $this->updateStat($type, 'totalsize', $size);
        $this->stats[$type]['minsize'] = min($this->stats[$type]['minsize'] ?: PHP_INT_MAX, $size);
        $this->stats[$type]['maxsize'] = max($this->stats[$type]['maxsize'], $size);
        // minsize et maxsize existent forcément car on a appellé updateStat()

        $this->updateStat($type, 'nbindex', 1);
    }

    /**
     * Supprime un document de l'index.
     *
     * Aucune erreur n'est générée si le document indiqué n'existe pas dans l'index Elastic Search.
     *
     * @param string $type Le type du document.
     * @param scalar $id L'identifiant du document.
     * @param string $esType Nom du mapping ElasticSearch à utiliser si différent de $type.
     */
    public function delete($type, $id, $esType = null)
    {
        // Format d'une commande "bulk delete" pour ES
        static $format = "{\"delete\":{\"_type\":%s,\"_id\":%s}}\n";

        // Vérifie le type et l'id
        $this->checkType($type)->checkId($id);

        // esType sert à initialiser _type, par défaut est égal à type, différent pour un Database
        is_null($esType) && $esType = $type;
        $this->esType[$esType] = $type;

        $this->log && $this->log->info('delete({type},{id})', [
            'type' => $type,
            '_type' => $esType,
            'id' => $id]
        );

        // Flushe le buffer si nécessaire
        $this->maybeFlush();

        // Stocke la commande dans le buffer
        $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        $this->bulk .= sprintf($format, json_encode($esType, $options), json_encode($id, $options));
        ++$this->bulkCount;

        // Met à jour la statistique sur le nombre de documents supprimés
        $this->updateStat($type, 'nbdelete', 1);
    }

    /**
     * Flushe le buffer si c'est nécessaire, c'est-à-dire si les limites sont atteintes.
     *
     * @return self
     */
    protected function maybeFlush()
    {
        if ($this->bulkCount < $this->bulkMaxCount && strlen($this->bulk) < $this->bulkMaxSize) {
            return $this;
        }

        return $this->flush();
    }

    /**
     * Flushe le buffer.
     *
     * @action "docalist_search_before_flush(count,size)" déclenchée avant que le flush ne commence.
     * @action "docalist_search_after_flush(count,size)" déclenchée une fois le flush terminé.
     *
     * @return self
     */
    public function flush()
    {
        // Regarde si on a des commandes en attente
        if (! $this->bulkCount) {
            return $this;
        }

        // Stocke la taille actuelle du buffer
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
        $this->log && $this->log->info('flush()', ['count' => $count, 'size' => $size]);
        do_action('docalist_search_before_flush', $count, $size);

        // Envoie le buffer à ES
        $alias = $this->settings->index() . '_write'; // garder synchro avec createIndex()
        $result = docalist('elastic-search')->bulk("/$alias/_bulk", $this->bulk);
        // @todo : permettre un timeout plus long pour les requêtes bulk
        // @todo si erreur, réessayer ? (par exemple avec un timeout plus long)

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
                        $this->log && $this->log->error('Indexing error', ['item' => $item]);
                    } else {
                        $type = $this->esType[$item->_type];
                        $this->updateStat($type, 'indexed', 1);
                        if ($item->_version === 1) {
                            $this->updateStat($type, 'added', 1);
                        } else {
                            $this->updateStat($type, 'updated', 1);
                        }
                    }
                } elseif (isset($item->delete)) {
                    $item = $item->delete;
                    $type = $this->esType[$item->_type];
                    $this->updateStat($type, 'deleted', 1);
                } else {
                    printf(
                        "<p style='color:red'>Unknown bulk response type:<pre>%s</pre></p>",
                        json_encode($item, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
                    );
                    $this->log && $this->log->error('Unknown bulk response type', ['item' => $item]);

                }
            }
        } else {
            // @todo: signaler une erreur ?
        }

        // Réinitialise le buffer
        $this->bulk = '';
        $this->bulkCount = 0;

        // Informe qu'on a flushé
        do_action('docalist_search_after_flush', $count, $size);
        $this->log && $this->log->info('flush done', ['count' => $count, 'size' => $size]);

        return $this;
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
    public function reindex($types = null)
    {
        // Si aucun type n'a été indiqué, on réindexe tout
        is_null($types) && $types = $this->settings->types();

        // Vérifie que les types indiqués sont indexés et récupère leurs libellés
        $types = (array) $types;
        $temp = [];
        foreach ($types as $type) {
            $this->checkType($type);
            $temp[$type] = $this->getIndexer($type)->getLabel();
        }
        $types = $temp;

        $this->log && $this->log->info('reindex()', ['types' => $types]);

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
            $this->updateStat($type, 'start', $startTime);

            $this->log && $this->log->debug('start reindex({type})', ['type' => $type]);

            // Informe qu'on va réindexer $type
            do_action('docalist_search_before_reindex_type', $type, $label);

            // Demande à l'indexeur de réindexer tous les contenus qu'il gère
            $this->getIndexer($type)->indexAll($this);

            // Vide le buffer
            $this->flush();

            // Met à jour les statistiques sur le temps écoulé
            $endTime = microtime(true);
            $this->updateStat($type, 'end', $endTime);
            $this->updateStat($type, 'time', round($endTime - $startTime, 3));

            // Calcule la taille moyenne des documents
            if ($nb = $this->stats[$type]['indexed']) {
                $avg = round($this->stats[$type]['totalsize'] / $nb, 0);
                $this->updateStat($type, 'avgsize', $avg);
            }

            // Informe qu'on a terminé la réindexation de $type
            do_action('docalist_search_after_reindex_type', $type, $label, $this->stats[$type]);

            $this->log && $this->log->debug('done reindex({type})', ['type' => $type]);
        }

        // Calcule les stats globales
        $this->computeTotalStats();

        // Informe que la réindexation de tous les types est terminée
        do_action('docalist_search_after_reindex', $types, $this->stats);

        $this->log && $this->log->info('reindex completed()', ['types' => $types, 'stats' => $this->stats]);

        return $this;
    }

    /**
     * Vérifie que la réponse passée en paramètre est un objet "acknowledged:true".
     *
     * @param string $message Message à afficher si la réponse n'a pas le format attendu.
     * @param mixed $response Réponse à tester.
     *
     * @return self
     */
    private function checkAcknowledged($message, $response)
    {
        if (is_object($response) && isset($response->acknowledged) && $response->acknowledged === true) {
            return $this;
        }

        throw new RuntimeException(sprintf(
            'Error while %s, expected {"acknowledged": true}, got %s',
            $message,
            json_encode($response)
        ));
    }

    /**
     * Vérifie que le type passé en paramètre est un type indexé.
     *
     * @param string $type Le nom du type à vérifier.
     *
     * @return self
     *
     * @throws InvalidArgumentException Si le type n'est pas indexé.
     */
    private function checkType($type = null)
    {
        static $types = null;

        is_null($types) && $types = array_flip($this->settings->types());

        if (! isset($types[$type])) {
            throw new InvalidArgumentException("Type '$type' is not indexed");
        }

        return $this;
    }

    /**
     * Vérifie que le paramètre peut être utilisé comme identifiant pour un document Elastic Search.
     *
     * @param mixed $id l'identifiant à vérifier
     *
     * @return self
     *
     * @throws InvalidArgumentException Si l'identifiant n'est pas un scalaire.
     */
    private function checkId($id)
    {
        if (! is_scalar($id)) {
            throw new InvalidArgumentException('Invalid document ID');
        }

        return $this;
    }

    /**
     * Met à jour une statistique.
     *
     * @param string $type Le type concerné.
     * @param string $stat La statistique à mettre à jour.
     * @param int $increment L'incrément qui sera ajouté à la statistique.
     */
    private function updateStat($type, $stat, $increment)
    {
        if (! isset($this->stats[$type])) {
            $this->stats[$type] = [
                'nbindex' => 0,     // nombre de fois où la méthode index() a été appellée
                'nbdelete' => 0,    // nombre de fois où la méthode delete() a été appellée

                'totalsize' => 0,   // Taille totale des docs passés à la méthode index(), tels que stockés dans le buffer (tout compris)
                'minsize' => 0,     // Taille du plus petit document indexé
                'avgsize' => 0,     // Taille moyenne des docs passés à la méthode index(), tels que stockés dans le buffer (=totalsize / indexed)
                'maxsize' => 0,     // Taille du plus grand document indexé

                'indexed' => 0,     // Nombre de docs que ES a effectivement indexé, suite à une commande index() (= added + updated)
                'deleted' => 0,     // Nombre de docs que ES a effectivement supprimé, suite à une commande delete()
                'added' => 0,       // Nombre de documents indexés par ES (indexed) qui n'existaient pas encore (= indexed - updated)
                'updated' => 0,     // Nombre de documents indexés par ES (indexed) qui existaient déjà (= indexed - added)

                'start' => 0,       // Timestamp de début de la réindexation
                'end' => 0,         // Timestamp de fin de la réindexation
                'time' => 0,        // Durée de la réindexation (en secondes) (=end-start)
            ];
        }

        $this->stats[$type][$stat] += $increment;
    }

    /**
     * Calcule les statistiques globales (somme des stats par type) et stocke le résultat dans la colonne 'total'.
     *
     * @return self
     */
    private function computeTotalStats()
    {
        // Remarque : array_sum = php >= 5.5 (utiliser wp_list_pluck() sinon)
        $total = [];
        $total['nbindex'] = array_sum(array_column($this->stats, 'nbindex'));
        $total['nbdelete'] = array_sum(array_column($this->stats, 'nbdelete'));
        $total['totalsize'] = array_sum(array_column($this->stats, 'totalsize'));
        $total['indexed'] = array_sum(array_column($this->stats, 'indexed'));
        $total['deleted'] = array_sum(array_column($this->stats, 'deleted'));
        $total['added'] = array_sum(array_column($this->stats, 'added'));
        $total['updated'] = array_sum(array_column($this->stats, 'updated'));
        $total['minsize'] = min(array_column($this->stats, 'minsize'));
        $total['maxsize'] = max(array_column($this->stats, 'maxsize'));
        $total['avgsize'] = $total['indexed'] ? round($total['totalsize'] / $total['indexed'], 0) : 0;
        $total['start'] = min(array_column($this->stats, 'start'));
        $total['end'] = max(array_column($this->stats, 'end'));
        $total['time'] = round($total['end'] - $total['start'], 3);

        $this->stats['Total'] = $total;

        return $this;
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
        $es = docalist('elastic-search'); /** @var ElasticSearchClient $es */

        try {
            $status = $es->head('/{index}');
        } catch (Exception $e) {
            return 0;   // Le serveur ne répond pas
        }

        switch ($status) {
            case 404: return 1; // Le serveur répond mais l'index n'existe pas
            case 200: return 2; // Le serveur répond et l'index existe
            default: throw new RuntimeException("Unknown ping status $status");
        }
    }
}
