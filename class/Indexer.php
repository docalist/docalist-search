<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2012-2014 Daniel Ménard
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
     * La liste des types de contenus à indexer.
     *
     * Initialisée lors du premier appel à checkType() en appellant le filtre
     * "docalist_search_get_types" et en croisant avec les types que
     * l'administrateur a choisit d'indexer (settings.types).
     *
     * @var array Un tableau de la forme type => label
     */
    protected $types;

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
    protected $bulk;

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
    protected $bulkCount;

    /**
     * Des statistiques sur la réindexation.
     *
     * @var array Un tableau de la forme type => statistiques
     *
     * cf. updateType() pour le détail des statistiques générées pour chaque
     * type.
     */
    protected $stats;

    /**
     * Construit un nouvel indexeur.
     *
     * @param IndexerSettings $settings
     */
    public function __construct(IndexerSettings $settings) {
        $this->settings = $settings;
        $this->bulkMaxSize = $settings->bulkMaxSize() * 1024 * 1024; // en Mo dans la config
        $this->bulkMaxCount = $settings->bulkMaxCount();
        $this->bulk = '';
        $this->bulkCount = 0;
        $this->stats = array();
        // $this->types est initialisé lors du premier appel à checkType()
    }

    /**
     * Destructeur. Flushe le buffer s'il y a des documents en attente.
     */
    public function __destruct() {
        $this->flush();
        // @todo: un destructeur ne doit pas générer d'exception (cf php).
    }

    /**
     * Vérifie que le type passé en paramètre est un type indexé.
     *
     * Un type est indexé si :
     * - c'est un type enregistré (qui figure dans la liste retournée par le
     *   filtre docalist_search_get_types)
     * - c'est un type que l'administrateur a choisit d'indexer (il est
     *   sélectionné dans la page de paramètres de Docalist Search).
     *
     * @param string|string[] $type Le nom de type à vérifier ou un tableau
     * de noms de types à vérifier.
     *
     * @throws RuntimeException Si aucun type n'est indexé.
     * @throws InvalidArgumentException Si le type est incorrect.
     */
    protected function checkType($type = null) {
        // Au premier appel, construit la liste des types indexés
        if (is_null($this->types)) {
            // Récupère la liste de tous les types indexables
            $all = apply_filters('docalist_search_get_types', array());

            // Récupère la liste des types indexés (choisis par l'admin)
            $selected = array_flip($this->settings->types());

            // Croise les deux
            $this->types = array_intersect_key($all, $selected);

            // Vérifie qu'on a quelque chose...
            if (empty($this->types)) {
                $msg = __('Aucun contenu indexable, vérifiez les paramètres de Docalist Search.', 'docalist-search');
                throw new RuntimeException($msg);
            }
        }

        // Vérifie que types passés en paramètre sont dans la liste
        foreach((array) $type as $type) {
            if (! isset($this->types[$type])) {
                $msg =__('Type invalide : %s (non indexé)', 'docalist-search');
                throw new InvalidArgumentException(sprintf($msg, $type));
            }
        }
    }

    /**
     * Vérifie que le paramètre peut être utilisé comme identifiant pour un
     * document Elastic Search.
     *
     * Pour le moment, on vérifie juste que c'est un scalaire.
     *
     * @param mixed $id l'identifiant à vérifier
     *
     * @throws InvalidArgumentException Si l'identifiant n'est pas un scalaire.
     */
    protected function checkId($id) {
        if (! is_scalar($id)) {
            $msg =__('ID invalide : %s (scalaire attentdu)', 'docalist-search');
            throw new InvalidArgumentException(sprintf($msg, $id));
        }
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
            $this->stats[$type] = array(
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
            );
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
        if (WP_DEBUG) {
            // Vérifie le type
            $this->checkType($type);

            // Vérifie l'id
            $this->checkId($id);

            // Vérifie le document
            if (! is_array($document)) {
                $msg =__('Document Docalist Search invalide : %s (tableau attendu)', 'docalist-search');
                throw new InvalidArgumentException(sprintf($msg, $type));
            }
        }

        // Format d'une commande "bulk index" pour ES
        $format = "{\"index\":{\"_type\":%s,\"_id\":%s}}\n%s\n";
        $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

        // Flushe le buffer si nécessaire
        $this->maybeFlush();

        // Stocke la commande dans le buffer
        $data = sprintf($format,
            json_encode($type, $options),
            json_encode($id, $options),
            json_encode($document, $options)
        );
        $this->bulk .= $data;
        ++$this->bulkCount;

        // Met à jour les statistiques sur la taille des documents
        $size = strlen($data);
        $this->updateStat($type, 'totalsize', $size);
        $this->stats[$type]['minsize'] = min($this->stats[$type]['minsize'] ?: PHP_INT_MAX, $size);
        $this->stats[$type]['maxsize'] = max($this->stats[$type]['maxsize'], $size);
        // minsize et maxsize existent forcèment car on a appellé totalsize avant

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
        // Vérifie le type
        $this->checkType($type);

        // Vérifie l'id
        $this->checkId($id);

        // Format d'une commande "bulk delete" pour ES
        $format = "{\"delete\":{\"_type\":%s,\"_id\":%s}}\n";
        $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

        // Flushe le buffer si nécessaire
        $this->maybeFlush();

        // Stocke la commande dans le buffer
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
        // Vérifie les types indiqués
        if (is_null($types)) {
            $types = array_keys($this->types); // tout
        } else {
            $types = (array) $types;
            foreach($types as $type) {
                $this->checkType($type);
            }
        }

        // Récupère les libellés des types
        $types = array_flip($types);
        foreach($types as $type => & $label) {
            $label = $this->types[$type];
        }
        unset($label);

        // Informe qu'on va commencer une réindexation
        do_action('docalist_search_before_reindex', $types);

        // Créé l'index, les mappings si pas fait, met à jour sinon
        $this->setup();

        // Vide le buffer (au cas où) pour que les stats soient justes
        $this->flush();

        // Permet au script de s'exécuter aussi longtemps que nécessaire
        set_time_limit(3600);
        ignore_user_abort(true);

        $es = docalist('elastic-search');

        // Réindexe chacun des types demandé
        foreach($types as $type => $label) {
            // Démarre le chronomètre et stocke l'heure de début dans les stats
            $startTime = microtime(true);
            $this->updateStat($type, 'start', $startTime);

            // Récupère l'heure actuelle du serveur ES (pour purger les docs)
            $lastUpdate = $this->lastUpdate($es);

            // Informe qu'on va réindexer $type
            do_action('docalist_search_before_reindex_type', $type, $label);

            // Demande au plugin de réindexer sa collection
            if (has_action("docalist_search_reindex_{$type}")) {
                do_action("docalist_search_reindex_{$type}", $this);
            } else {
                throw new RuntimeException("Aucune action enregistrée pour 'docalist_search_reindex_{$type}'");
            }

            // Vide le buffer
            $this->flush();

            // Supprime les posts qui n'ont pas été mis à jour
            if (! is_null($lastUpdate)) {

                // Force un rafraichissement des index
                // @see http://www.elasticsearch.org/guide/reference/api/admin-indices-refresh/
                $es->post("_refresh");

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

                $result = $es->post("$type/_count", $query);

                // Supprime ces documents via un deleteByQuery(_timestamp<start)
                if ($result->count) {
                    // @see http://www.elasticsearch.org/guide/reference/api/delete-by-query/
                    $es->delete("$type/_query", $query);
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
        }// end foreach($types)

        // Calcule les stats globales
        // @todo

        // Informe que la réindexation de tous les types est terminée
        do_action('docalist_search_after_reindex', $types, $this->stats);
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
            $data = $es->get('_search?sort=_timestamp:desc&size=1&fields');
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
        do_action('docalist_search_before_flush', $count, $size);

        // Envoie le buffer à ES
        $result = docalist('elastic-search')->bulk('_bulk', $this->bulk);
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
            $types = array_keys($this->types);
        }

        // On ne vérifie pas que les types indiqués existent pour permettre
        // de supprimer un type qui n'est plus indexé.

        // ES 0.90.3 ne supporte pas un appel de la forme suivante :
        // $es->delete(implode(',', (array)$types));
        // donc on supprime les types demandés un par un.

        $es = docalist('elastic-search');

        // Supprime tous les types indiqués
        foreach($types as $type) {
            // @see http://www.elasticsearch.org/guide/reference/api/admin-indices-delete-mapping/
            $es->delete($type);
        }
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
        $es = docalist('elastic-search');

        try {
            $status = $es->head();
        }
        catch (Exception $e) {
            return 0;   // Le serveur ne répond pas
        }

        switch ($status) {
            case 404: return 1; // Le serveur répond mais l'index n'existe pas
            case 200: return 2; // Le serveur répond et l'index existe
            default: throw new RuntimeException("unknown ping status $status");
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
        // Vérifie que la liste des types est chargée
        $this->checkType();

        // Détermine les settings de l'index et permet aux types de les modifier
        $settings = $this->defaultIndexSettings();
        foreach($this->types as $type => $label) {
            $settings = apply_filters("docalist_search_get_{$type}_settings", $settings);
        }

        $es = docalist('elastic-search');

        // Cas 1. L'index n'existe pas encore, on le crée
        if (! $es->exists()) {
            // @see http://www.elasticsearch.org/guide/reference/api/admin-indices-create-index/
            $es->put('', $settings);
        }

        // Cas 2. L'index existe déjà, maj les settings, supprime les vieux types
        else {
            // A. Supprime de l'index les types existants qui ne sont plus indexés

            // Récupère tous les types qui existent (les mappings)
            // @see http://www.elasticsearch.org/guide/reference/api/admin-indices-get-mapping/
            $types = $es->get('_mapping');

            // La réponse est de la forme : { "index":{"type1":{...}, "type2":{...}}
            // Comme on veut juste les noms des types, on supprime l'étage "index"
            $types = $types->{key($types)};

            // On ne veut que les noms des types
            $types = array_keys((array) $types);

            // Détermine ceux qu'on n'indexe plus : diff (old, new)
            $types = array_diff($types, array_keys($this->types));

            // Suppression
            $types && $this->clear($types);

            // @todo : ne supprimer que des types qu'on a nous-mêmes créé ?
            // stocker un "_meta" dans le type, interdire suppression si absent

            // B. Met à jour les settings de l'index
            // @see http://www.elasticsearch.org/guide/reference/api/admin-indices-update-settings/
            // Remarque : Pour mettre à jour les analyseurs, il faut fermer
            // puis réouvrir l'index
            $es->post('_close');
            $es->put('_settings', $settings);
            $es->post('_open');
        }

        // Enregistre (ou met à jour) les mappings de chaque type
        foreach($this->types as $type => $label) {
            // Récupère les mappings de ce type
            $mapping = apply_filters("docalist_search_get_{$type}_mappings", array());

            // Pour purger les index lors d'une réindexation, il nous faut _timestamp
            $mapping['_timestamp'] = array(
                'enabled' => true,
                // 'store' => true, // utile à activer pour debug
            );

            // Si le mapping est vide, json_encode va générer un tableau vide
            // alors que ES attend un objet. Dans ce cas, cela génère ensuite une
            // exception "ArrayList cannot be cast to Map" dans ES.
            // empty($mapping) && $mapping = (object) $mapping;
            // comme on _timestamp, ne peut plus être vide

            // Stocke le mapping
            $mapping = array($type => $mapping);
            $es->put("$type/_mapping", $mapping);

            // @todo Tester si le mapping contient des erreurs.
        }

        // Remarque : on pourrait facilement envoyer tous les mappings ES en une
        // seule requête mais si un mapping contient des erreurs, c'est plus
        // difficile d'identifier le mapping fautif.
        // Donc on les envoie séparément.
    }

    /**
     * Retourne les settings par défaut utilisés lorsqu'un index est créé.
     *
     * @return array
     */
    protected function defaultIndexSettings() {
        return require_once __DIR__ . '/../mappings/default-index-settings.php';
    }
}