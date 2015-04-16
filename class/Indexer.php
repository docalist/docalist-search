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
 * @version     SVN: $Id$
 */
namespace Docalist\Search;

use Exception, InvalidArgumentException, RuntimeException;
use Docalist\Biblio\Reference;
use Docalist\Search\NullIndexer;
use Psr\Log\LoggerInterface;

/**
 * L'indexeur
 */
class Indexer {
    /**
     * La configuration du moteur de recherche
     * (passée en paramètre au constructeur).
     *
     * @var IndexerSettings
     */
    protected $settings;

    /**
     * Le logger à utiliser.
     *
     * @var LoggerInterface
     */
    protected $log;

    /**
     * Liste des indexeurs activés, sous la forme type => TypeIndexer.
     *
     * @var TypeIndexer[]
     */
    protected $indexers = [];

    /**
     * Un buffer qui accumulent les documents à envoyer au serveur ES.
     *
     * Quand on ajoute ou qu'on supprime des documents, les "commandes"
     * correspondantes sont stockées dans le buffer. A la fin de la requête, ou
     * bien lorsque le buffer atteint sa taille maximale, le buffer est envoyé
     * au server ES puis il est réinitialisé.
     *
     * Dans le buffer, les commandes sont stockées en JSON, dans le format
     * attendu par l'API "bulk" de Elastic Search
     * (http://www.elasticsearch.org/guide/reference/api/bulk/).
     *
     * Exemple :
     *
     * <code>
     * {"index":{"_type": "dclref", "_id":1234}}\n      // indexer cette notice
     * {"ref":12,"title":"test1", etc.}\n
     * {"delete":{"_type": "post", "_id":13}}\n         // supprimer cet article
     * {"index":{"_type": "dclresource", "_id":5678}}\n // indexer cette ressource
     * {"ref":25,"title":"test2", etc.}\n
     * etc.
     * </code>
     *
     * La taille maximale du buffer est déterminée par les paramètres
     * "bulkMaxSize" et "bulkMaxCount". Le buffer est flushé dès que l'une de
     * ces deux limites est atteinte ou bien lorsque la requête se termine
     * (appel du destructeur de cette classe). Il est également possible de
     * forcer l'envoi des commandes en attente et de vider le buffer en
     * appellant la méthode flush().
     *
     * @var string
     */
    protected $bulk = '';

    /**
     * La taille maximale, en octets, autorisée pour le buffer
     * (settings.bulkMaxSize).
     *
     * @var int
     */
    protected $bulkMaxSize;

    /**
     * Le nombre maximum de documents autorisés dans le buffer
     * (settings.bulkMaxCount).
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
     * cf. updateType() pour le détail des statistiques générées pour chaque
     * type.
     */
    protected $stats = [];

    /**
     * Construit un nouvel indexeur.
     *
     * @param IndexerSettings $settings
     */
    public function __construct(Settings $settings) {
        // Stocke les paramètres de l'indexeur
        $this->settings = $settings->indexer;

        // Récupère le logger à utiliser
        $this->log = docalist('logs')->get('indexer');

        // Initialise les paramètres du buffer
        $this->bulkMaxSize = $this->settings->bulkMaxSize() * 1024 * 1024; // en Mo dans la config
        $this->bulkMaxCount = $this->settings->bulkMaxCount();

        // Active l'indexation en temps réel
        if ($settings->realtime()) {
            // On utilise l'action wp_loaded pour être sûr que tous les plugins
            // sont chargés et ont eu le temps d'installer leurs filtres.
            add_action('wp_loaded', function() {
                foreach ($this->settings->types() as $type) {
                    $this->log && $this->log->debug('Activate realtime indexing for type {type}', ['type' => $type]);
                    $this->indexer($type)->realtime();
                }
            });
        }
    }

    /**
     * Destructeur. Flushe le buffer s'il y a des documents en attente.
     */
    public function __destruct() {
        $this->log && $this->log->debug('Indexer::__destruct() : calling flush()');
        $this->flush();
        // @todo: un destructeur ne doit pas générer d'exception (cf php).
    }

    /**
     * Retourne la liste des types de contenus indexables.
     *
     * @param bool $sortByLabel Par défaut, les types sont retournés dans
     * l'ordre où ils sont déclarés. Si vous passez $sortByLabel=true, le
     * tableau retourné est trié par libellé (natcasesort).
     *
     * @return array Un tableau de la forme type => libellé
     */
    public function availableTypes($sortByLabel = false) {
        $types = apply_filters('docalist_search_get_types', []);

        $this->log && $this->log->debug('availableTypes()', $types);

        $sortByLabel && natcasesort($types);

        return $types;
    }

    /**
     * Retourne la liste des types de contenus indexés.
     *
     * @param bool $sortByLabel Par défaut, les types sont retournés dans
     * l'ordre où ils sont déclarés. Si vous passez $sortByLabel=true, le
     * tableau retourné est trié par libellé (natcasesort).
     *
     * @return array Un tableau de la forme type => libellé
     */
    public function indexedTypes($sortByLabel = false) {
        $types = $this->initLabels($this->settings->types());

        $this->log && $this->log->debug('indexedTypes()', $types);

        $sortByLabel && natcasesort($types);

        return $types;
    }

    /**
     * Récupère le libellé des types passés en paramètre.
     *
     * @param array $types Un tableau contenant la liste des types.
     *
     * @return array Un tableau de la forme type => libellé.
     */
    private function initLabels(array $types) {
        $labels = $this->availableTypes();

        $types = array_flip($types);
        foreach($types as $type => & $label) {
            $label = isset($labels[$type]) ? $labels[$type] : $type;
        }
        unset($label);

        return $types;
    }

    /**
     * Retourne l'indexeur utilisé pour un type de contenu donné.
     *
     * Si l'indexeur à utiliser pour ce type n'a pas encore été créé, il est
     * instancié lors du premier appel à la méthode.
     *
     * @param string $type Le type de contenu.
     *
     * @return TypeIndexer L'indexeur utilisé pour ce type de contenu.
     *
     * @throws InvalidArgumentException Si le type indiqué n'est pas indexé
     * (dans les paramètres du moteur de recherche) ou si aucun indexeur n'est
     * disponible pour ce type.
     */
    public function indexer($type) {
        if (!isset($this->indexers[$type])) {
            // Récupère l'indexeur à utiliser pour ce type
            $indexer = apply_filters("docalist_search_get_{$type}_indexer", null);

            $this->log && $this->log->debug('indexer({type})', ['type' => $type, 'indexer' => $indexer]);

            // Génère une exception si on n'a pas d'indexeur
            if (is_null($indexer)) {
                docalist('admin-notices')->warning("Warning: indexer for type '$type' is not available", 'docalist-search');
                $indexer = new NullIndexer();
            }

            // Génère une exception si ce n'est pas un TypeIndexer
            if (! $indexer instanceof TypeIndexer) {
                docalist('admin-notices')->error("Error: invalid indexer for type '$type'", 'docalist-search');
                $indexer = new NullIndexer();
            }

            // Ok
            $this->indexers[$type] = $indexer;
        }

        return $this->indexers[$type];
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
    protected function checkType($type = null) {
        static $types = null;

        is_null($types) && $types = array_flip($this->settings->types());

        if (! isset($types[$type])) {
            throw new InvalidArgumentException("Type '$type' is not indexed");
        }

        return $this;
    }

    /**
     * Vérifie que le paramètre peut être utilisé comme identifiant pour un
     * document Elastic Search.
     *
     * @param mixed $id l'identifiant à vérifier
     *
     * @return self
     *
     * @throws InvalidArgumentException Si l'identifiant n'est pas un scalaire.
     */
    protected function checkId($id) {
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
    protected function updateStat($type, $stat, $increment) {
        if (! isset($this->stats[$type])) {
            $this->stats[$type] = [
                'nbindex' => 0,     // nombre de fois où la méthode index() a été appellée
                'nbdelete' => 0,    // nombre de fois où la méthode delete() a été appellée
                'removed' => 0,     // nombre de documents non réindexés, supprimés via deleteByQuery (purgés)

                'totalsize' => 0,   // Taille totale des docs passés à la méthode index(), tels que stockés dans le buffer (tout compris)
                'minsize' => 0,     // Taille du plus petit document indexé
                'avgsize' => 0,     // Taille moyenne des docs passés à la méthode index(), tels que stockés dans le buffer (=totalsize / indexed)
                'maxsize' => 0,     // Taille du plus grand document indexé

                'indexed' => 0,     // Nombre de docs que ES a effectivement indexé, suite à une commande index() (= added + updated)
                'deleted' => 0,     // Nombre de docs que ES a effectivement supprimé, suite à une commande delete()
                'added' => 0,       // Nombre de documents indexés par ES (indexed) qui n'existaient pas encore (= indexed - updated)
                'updated' => 0,     // Nombre de documents indexés par ES (indexed) qui existaient déjà (= indexed - added)

                'nbflush' => 0,     // not implemented (comment connaitre le type ?)

                'start' => 0,       // Timestamp de début de la réindexation
                'end' => 0,         // Timestamp de fin de la réindexation
                'time' => 0,        // Durée de la réindexation (en secondes) (=end-start)
            ];
        }

        $this->stats[$type][$stat] += $increment;
    }

    /**
     * Ajoute ou met à jour un document dans l'index.
     *
     * Si le document indiqué existe déjà dans l'index Elastic Search, il est
     * mis à jour, sinon il est créé.
     *
     * Il n'y a pas d'attribution automatique d'ID : vous devez fournir l'ID
     * du document à indexer.
     *
     * @param string $type Le type du document.
     * @param scalar $id L'identifiant du document.
     * @param array $document Les données du document.
     */
    public function index($type, $id, $document) {
        // Format d'une commande "bulk index" pour ES
        static $format = "{\"index\":{\"_type\":%s,\"_id\":%s}}\n%s\n";

        // Vérifie le type et l'id
        $this->checkType($type)->checkId($id);

        // Vérifie le document
        if (! is_array($document)) {
            throw new InvalidArgumentException("Invalid document");
        }

        $this->log && $this->log->info('index({type},{id})', ['type' => $type, 'id' => $id, 'document' => $document]);

        // Flushe le buffer si nécessaire
        $this->maybeFlush();

        // Stocke la commande dans le buffer
        $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        $document = json_encode($document, $options);
        $this->bulk .= sprintf($format,
            json_encode($type, $options),
            json_encode($id, $options),
            $document
        );
        ++$this->bulkCount;

        // Met à jour les statistiques sur la taille des documents
        $size = strlen($document);
        $this->updateStat($type, 'totalsize', $size);
        $this->stats[$type]['minsize'] = min($this->stats[$type]['minsize'] ?: PHP_INT_MAX, $size);
        $this->stats[$type]['maxsize'] = max($this->stats[$type]['maxsize'], $size);
        // minsize et maxsize existent forcèment car on a appellé updateStat()

        $this->updateStat($type, 'nbindex', 1);
    }

    /**
     * Supprime un document de l'index.
     *
     * Aucune erreur n'est générée si le document indiqué n'existe pas dans
     * l'index Elastic Search.
     *
     * @param string $type Le type du document.
     * @param scalar $id L'identifiant du document.
     */
    public function delete($type, $id) {
        // Format d'une commande "bulk delete" pour ES
        static $format = "{\"delete\":{\"_type\":%s,\"_id\":%s}}\n";

        // Vérifie le type et l'id
        $this->checkType($type)->checkId($id);

        $this->log && $this->log->info('delete({type},{id})', ['type' => $type, 'id' => $id]);

        // Flushe le buffer si nécessaire
        $this->maybeFlush();

        // Stocke la commande dans le buffer
        $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        $this->bulk .= sprintf($format,
            json_encode($type, $options),
            json_encode($id, $options)
        );
        ++$this->bulkCount;

        $this->updateStat($type, 'nbdelete', 1);
    }

    /**
     * Réindexe un ou plusieurs types de documents.
     *
     * La méthode flush() est appellée après chaque type.
     *
     * @param string|array|null $types un type, une liste de type ou null pour
     * réindexer tous les types indexés.
     *
     * @action "docalist_search_before_reindex(types)" déclenchée avant que la
     * réindexation ne démarre. Reçoit en paramètre un tableau $types
     * contenant la liste des types qui vont être réindexés.
     *
     * @action "docalist_search_before_reindex_type(type,label)" déclenchée
     * quand la réindexation du type $type commence.
     *
     * @action "docalist_search_reindex_$type" déclenchée pour réindexer les
     * documents du type $type. Le plugin qui gère ce type doit parcourir sa
     * collection de documents et appeller la méthode index() pour chaque
     * document.
     *
     * @action "docalist_search_after_reindex_type(type,label,stats)" déclenchée
     * quand la réindexation du type $type est terminée.
     *
     * @action "docalist_search_after_reindex(types,stats)" : déclenchée quand la
     * réindexation est terminée.
     */
    public function reindex($types = null) {
        // Si aucun type n'a été indiqué, on réindexe tout
        if (is_null($types)) {
            $types = $this->indexedTypes(); // contient les libellés
        } else {
            // Vérifie que les types indiqués sont indexés
            $types = (array) $types;
            foreach($types as $type) {
                $this->checkType($type);
            }

            // Récupère les libellés des types indiqués
            $types = $this->initLabels($types);
        }

        $this->log && $this->log->info('reindex()', ['types' => $types]);

        // Informe qu'on va commencer une réindexation
        do_action('docalist_search_before_reindex', $types);

        // Créé l'index, les mappings si pas fait, met à jour sinon
        $this->setup();

        // Vide le buffer (au cas où) pour que les stats soient justes
        $this->flush();

        // Permet au script de s'exécuter aussi longtemps que nécessaire
        set_time_limit(3600);
        ignore_user_abort(true);

        // Récupère la connexion elastic search
        $es = docalist('elastic-search'); /* @var $es ElasticSearchClient */

        // Réindexe chacun des types demandé
        foreach($types as $type => $label) {
            // Démarre le chronomètre et stocke l'heure de début dans les stats
            $startTime = microtime(true);
            $this->updateStat($type, 'start', $startTime);

            $this->log && $this->log->debug('start reindex({type})', ['type' => $type]);

            // Récupère l'heure actuelle du serveur ES (pour purger les docs)
            $lastUpdate = $this->lastUpdate($es);

            // Informe qu'on va réindexer $type
            do_action('docalist_search_before_reindex_type', $type, $label);


            // Demande à l'indexeur de réindexer ses contenus
            $this->indexer($type)->indexAll($this);

            // Vide le buffer
            $this->flush();

            // Supprime les posts qui n'ont pas été mis à jour
            if (! is_null($lastUpdate)) {

                // Force un rafraichissement des index
                // @see http://www.elasticsearch.org/guide/reference/api/admin-indices-refresh/
                $es->post('/{index}/_refresh');

                // Remarque : le refresh n'est nécessaire que pour avoir de
                // manière précise le nombre de docs qui vont être supprimés.
                // En interne, deleteByQuery fait déjà un refresh :
                // @see https://github.com/elasticsearch/elasticsearch/issues/3593
                // Si on n'avait pas besoin des stats, ce serait inutile.

                // Compte les documents de type $type dont timestamp < start
                // http://www.elasticsearch.org/guide/reference/api/count/
                // $query = sprintf('{"range":{"_timestamp":{"lt":%.0f}}}', $lastUpdate);

                // ES-1.0 : count now requires a top-level "query" parameter
                // @see http://www.elasticsearch.org/guide/en/elasticsearch/reference/master/_search_requests.html
                $query = sprintf('{"query":{"range":{"_timestamp":{"lt":%.0f}}}}', $lastUpdate);

                $result = $es->post("/{index}/$type/_count", $query);


                // Supprime ces documents via un deleteByQuery(_timestamp<start)
                if ($result->count) {
                    $this->log && $this->log->debug('Purge {count} old {type}', ['type' => $type, 'count' => $result->count]);

                    // @see http://www.elasticsearch.org/guide/reference/api/delete-by-query/
                    $es->delete("/{index}/$type/_query", $query);
                } else {
                    $this->log && $this->log->debug('Nothing to purge for type {type}', ['type' => $type]);
                }

                // Met à jour la statistique sur le nombre de documents supprimés
                $this->updateStat($type, 'removed', $result->count);

                // Remarque : en théorie, il peut se passer des choses dans
                // l'index entre le moment où on compte le nombre de documents
                // à supprimer et le moment où on lance la commande la
                // suppression. Cependant, aucune opération ne peut générer un
                // document avec un timestamp antérieur à notre date de début
                // donc on ne risque pas de supprimer des documents introduits
                // par un tiers. Le seul cas possible, ce serait si un tiers
                // fixait lui-même le timestamp a stocker dans le document
                // (avec version_type=external), ce qui est exclus.
            }

            // Met à jour les statistiques sur le temps écoulé
            $endTime = microtime(true);
            $this->updateStat($type, 'end', $endTime);
            $this->updateStat($type, 'time', round($endTime - $startTime, 2));

            // Calcule la taille moyenne des documents
            if ($nb = $this->stats[$type]['indexed']) {
                $avg = round($this->stats[$type]['totalsize'] / $nb, 0);
                $this->updateStat($type, 'avgsize', $avg);
            }

            // Informe qu'on a terminé la réindexation de $type
            do_action('docalist_search_after_reindex_type', $type, $label, $this->stats[$type]);

            $this->log && $this->log->debug('done reindex({type})', ['type' => $type]);
        }// end foreach($types)

        // Calcule les stats globales
        // @todo

        // Informe que la réindexation de tous les types est terminée
        do_action('docalist_search_after_reindex', $types, $this->stats);

        $this->log && $this->log->info('reindex completed()', ['types' => $types, 'stats' => $this->stats]);
    }

    /**
     * Retourne la date/heure de dernière modification de l'index Elastic Search.
     *
     * Cette méthode est utilisée pour purger l'index, c'est-à-dire supprimer
     * les documents qui n'ont pas été réindexés depuis une certaine date.
     *
     * La méthode lance une recherche sur l'index en cours. Si l'index n'existe
     * pas ou s'il ne contient aucun document, la méthode retourne null : cela
     * signifie que l'index est vide et qu'il n'y a aucun document à purger.
     *
     * Dans le cas contraire, le champ _timestamp du dernier document ajouté ou
     * mis à jour est retourné.
     *
     * @param ElasticSearchClient $es
     *
     * @return int|null Retourne l'heure de dernière modification (en
     * millisecondes écoulées depuis epoch) ou null si l'index en cours ne
     * contient aucun document.
     *
     * Remarque : le temps retourné est en millisecondes (contrairement à la
     * fonction php microtime qui retourne des secondes).
     */
    protected function lastUpdate(ElasticSearchClient $es) {
        try {
            $data = $es->get('/{index}/_search?sort=_timestamp:desc&size=1&fields');
        } catch (Exception $e) {
            // l'index n'existe pas
            return null; // on ne sait pas
        }

        $time = isset($data->hits->hits[0]->sort[0]) ? $data->hits->hits[0]->sort[0] : null;

        return $time;
    }

    /**
     * Flushe le buffer.
     *
     * @action "docalist_search_before_flush(count,size)" déclenchée avant que
     * le flush ne commence.
     *
     * @action "docalist_search_after_flush(count,size)" déclenchée une fois le
     * flush terminé.
     */
    public function flush() {
        // Regarde si on a des commandes en attente
        if (! $this->bulkCount) {
            return 0;
        }

        // Réinitialise le cache wordpress
        wp_cache_init();

        // Stocke le nombre de commandes pour pouvoir le retourner après
        $count = $this->bulkCount;
        $size = strlen($this->bulk);

        // Informe qu'on va flusher
        $this->log && $this->log->info('flush()', ['count' => $count, 'size' => $size]);
        do_action('docalist_search_before_flush', $count, $size);

        // Envoie le buffer à ES
        $result = docalist('elastic-search')->bulk('/{index}/_bulk', $this->bulk);
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

//        $this->updateStat($item->_type, 'nbflush', 1);
        if (is_object($result) && isset($result->items)) {
            foreach($result->items as $item) {
                if (isset($item->index)) {
                    $item = $item->index;

                    if (! isset($item->_version)) {
                        echo "ERREUR LORS DE L'INDEXATION D'UN ITEM : ";
                        var_dump($item);
                        $this->log && $this->log->error('Indexing error', ['item' => $item]);
                    } else {
                        $this->updateStat($item->_type, 'indexed', 1);
                        if ($item->_version === 1) {
                            $this->updateStat($item->_type, 'added', 1);
                        } else {
                            $this->updateStat($item->_type, 'updated', 1);
                        }
                    }
                }

                elseif (isset($item->delete)) {
                    $item = $item->delete;
                    $this->updateStat($item->_type, 'deleted', 1);
                }
                else {
                    echo "bulk reponse, type d'item non géré : ", var_dump($item);
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
    }

    /**
     * Flushe le buffer si c'est nécessaire, c'est-à-dire si les limites sont
     * atteintes.
     *
     * @return int Le nombre de commandes envoyées à ES ou zéro si le buffer
     * n'avait pas besoin d'être flushé.
     */
    protected function maybeFlush() {
        if ($this->bulkCount < $this->bulkMaxCount && strlen($this->bulk) < $this->bulkMaxSize) {
            return;
        }

        $this->flush();
    }

    /**
     * Supprime un ou plusieurs types de l'index :
     * - supprime tous les documents de ce type dans l'index Elastic Search
     * - supprime les mappings du type
     *
     * Permet par exemple à un plugin de désindexer ses contenus avant d'être
     * désinstallé.
     *
     * Aucune erreur n'est générée si le type indiqué n'existe pas.
     *
     * Attention : le type indiqué n'est pas vérifié. S'il existe dans l'index,
     * il sera supprimé.
     *
     * Si aucun type n'est indiqué, tous les types indexés sont supprimés.
     * @todo : à revoir, supprimer tous les types existants dans l'index ?
     *
     * L'index lui-même n'est pas supprimé, même s'il se retrouve vide.
     *
     * @param string|array|null $types un type, une liste de type ou null pour
     * supprimer tous les types indexés.
     */
    public function clear($types = null) {
        // Vérifie les types
        if (is_null($types)) {
            $types = $this->settings->types(); // tout
        }

        // On ne vérifie pas que les types indiqués existent pour permettre
        // de supprimer des types qui ne sont plus indexés ou dispos.

        $this->log && $this->log->notice('clear types', $types);

        // @see http://www.elastic.co/guide/en/elasticsearch/reference/current/indices-delete-mapping.html
        docalist('elastic-search')->delete('/{index}/' . implode(',', $types));
    }

    /**
     * Ping le serveur ElasticSearch pour déterminer si le serveur répond et
     * tester si l'index existe.
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
    public function ping() {
        // Récupère la connexion elastic search
        $es = docalist('elastic-search'); /* @var $es ElasticSearchClient */

        try {
            $status = $es->head('/{index}');
        }
        catch (Exception $e) {
            return 0;   // Le serveur ne répond pas
        }

        switch ($status) {
            case 404: return 1; // Le serveur répond mais l'index n'existe pas
            case 200: return 2; // Le serveur répond et l'index existe
            default: throw new RuntimeException("Unknown ping status $status");
        }
    }

    /**
     * Initialise ou met à jour les paramètres de l'index Elastic Search.
     *
     * La méthode crée l'index s'il n'existe pas, met à jour ses settings,
     * supprime les types qui ne sont plus indexés et initialise ou met à jour
     * les mappings des types indexés.
     */
    public function setup() {
        $this->log && $this->log->debug('start setup');

        // Récupère la connexion elastic search
        $es = docalist('elastic-search'); /* @var $es ElasticSearchClient */

        // Détermine les settings de l'index
        $settings = apply_filters('docalist_search_get_index_settings', []);

        // Cas 1. L'index n'existe pas encore, on le crée
        // @see http://www.elastic.co/guide/en/elasticsearch/reference/current/indices-create-index.html
        if (! $es->exists('/{index}')) {
            $this->log && $this->log->info('create index', $settings);

            $es->put('/{index}', $settings);
        }

        // Cas 2. L'index existe déjà, maj les settings, supprime les vieux types
        else {
            $this->log && $this->log->info('update index', $settings);

            // A. Supprime de l'index les types existants qui ne sont plus indexés

            // Récupère tous les types qui existent (les mappings)
            // @see http://www.elastic.co/guide/en/elasticsearch/reference/current/indices-get-mapping.html
            $types = $es->get('/{index}/_mapping');

            // Extrait les noms des types existants. La réponse est de la forme :
            // {"wp_prisme":{"mappings":{"post":{...}},"page":{...}}}
            $index = key($types); // apparemment, key() est utilisable sur un tableau
            $types = array_keys((array)$types->$index->mappings);

            // Détermine ceux qu'on n'indexe plus : diff(old, new)
            $types = array_diff($types, $this->settings->types());

            // Suppression
            $types && $this->clear($types);

            // @todo : ne supprimer que des types qu'on a nous-mêmes créé ?
            // stocker un "_meta" dans le type, interdire suppression si absent

            // B. Met à jour les settings de l'index
            // @see http://www.elastic.co/guide/en/elasticsearch/reference/current/indices-update-settings.html
            // Remarque : Pour mettre à jour les analyseurs, il faut fermer
            // puis réouvrir l'index
            $es->post('/{index}/_close');
            $es->put ('/{index}/_settings', $settings);
            $es->post('/{index}/_open');

            // Depuis ES 1.5, il faut attendre après avoir modifié les settings,
            // sinon le prochain GET génère une exception :
            // "[RECOVERING] operations only allowed when started/relocated"
            $es->get('/_cluster/health/{index}?wait_for_status=yellow&timeout=10s');
        }

        // Enregistre (ou met à jour) les mappings

        // Remarque : ElasticSearch ne sait pas mettre à jours plusieurs
        // mappings d'un coup, donc il faut les envoyer un par un.

        foreach($this->settings->types() as $type) {
            // Récupère l'indexeur pour ce type
            $indexer = $this->indexer($type);

            // Récupère les mappings de ce type
            $mapping = $indexer->mapping();

            // Pour purger l'index lors d'une réindexation, on a besoin que le
            // champ _timestamp soit activé.
            // @see http://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-timestamp-field.html
            $mapping['_timestamp'] = ['enabled' => true];

            // Si le mapping est vide, json_encode va générer un tableau vide
            // alors que ES attend un objet, ce qui génère alors une exception
            // "ArrayList cannot be cast to Map". Mais dans notre cas, cela ne
            // peut pas se produire, car on a au minimum _timestamp.

            // Stocke le mapping
            $this->log && $this->log->notice('create/update mapping {type}', ['type' => $type, 'mapping' => $mapping]);

            $mapping = [$type => $mapping];
            $es->put("/{index}/$type/_mapping", $mapping);

            // @todo Tester si le mapping contient des erreurs.
        }
    }
}