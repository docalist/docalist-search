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

use WP_Query;
use Exception;

/**
 * La classe qui gère les recherches.
 */
class SearchEngine {

    /**
     * La configuration du moteur de recherche
     * (passée en paramètre au constructeur).
     *
     * @var Settings
     */
    protected $settings;

    /**
     * @var SearchRequest la requête adressée à ElasticSearch.
     */
    protected $request;

    /**
     * Les résultats de la recherche.
     *
     * @var SearchResults
     */
    protected $results;

    /**
     * Construit le moteur de recherche.
     *
     * @param Settings $settings
     */
    public function __construct(Settings $settings) {
        // Stocke nos paramètres
        $this->settings = $settings;

        // Intègre le moteur dans WordPress quand parseQuery() est exécutée
        add_filter('parse_query', [$this, 'onParseQuery']);

        // Crée la requête quand on est sur la page "liste des réponses"
        add_filter('docalist_search_create_request', function(SearchRequest $request = null, WP_Query $query) {
            if (is_null($request) && $query->is_page && $query->get_queried_object_id() === $this->searchPage()) {
                $request = $this->defaultRequest()
                    ->isSearch(true)
                    ->searchPageUrl($this->searchPageUrl());
            }

            return $request;
        }, 10, 2);

        // Crée le filtre par défaut pour les articles
        add_filter('docalist_search_get_post_filter', function($filter, $type) {
            return $this->defaultFilter($type);
        }, 10, 2);

        // Crée le filtre par défaut pour les pages
        add_filter('docalist_search_get_page_filter', function($filter, $type) {
            return $this->defaultFilter($type);
        }, 10, 2);

        // TODO : filtres à virer, utiliser docalist('docalist-search-engine')->xxx()
        add_filter('docalist_search_get_request', array($this, 'request'), 10, 0);
        add_filter('docalist_search_get_results', array($this, 'results'), 10, 0);
        add_filter('docalist_search_get_rank', array($this, 'rank'), 10, 1);
        add_filter('docalist_search_get_hit_link', array($this, 'hitLink'), 10, 1);
    }

    /**
     * Construit une requête standard en tenant compte du statut des documents
     * et des droits de l'utilisateur en cours.
     *
     * @param string $types Liste des types interrogés, par défaut tous les
     * types indexés.
     * @param boolean $ignoreQueryString Par défaut la requête tient compte des
     * arguments passés en query string. Passez false pour obtenir une requête
     * vide (match all) ne contenant que les filtres.
     *
     * @return SearchRequest
     */
    public function defaultRequest($types = null, $ignoreQueryString = false) {
        // Crée la requête
        $request = new SearchRequest($ignoreQueryString ? null : wp_unslash($_REQUEST));

        // Détermine les types à prendre en compte
        if (is_null($types)) {
            // Si la requête a déjà un filtre sur _type, on filtre uniquement ce type
            if ($request->hasFilter('_type')) {
                $types = array_keys($request->filter('_type'));
            }

            // Sinon on prend tous les types indexés
            else {
                $types = $this->settings->indexer->types();
            }
        } else {
            $types = (array) $types;
        }

        // Pour chaque type, construit le filtre de visibilité
        $filters = [];
        foreach($types as $type) {
            $filter = apply_filters("docalist_search_get_{$type}_filter", null, $type);
            $filter && $filters[] = $filter;
            // Remarque : si personne n'a créé de filtre, le type n'est pas
            // interrogeable, c'est mieux que de rendre tout visible.
        }

        // Combine tous les filtres ensmeble et ajoute à la requête
        $request->addHiddenFilter(['bool' => ['should' => $filters]]);

        // Ok
        return $request;
    }

    /**
     * Construit un filtre par défaut pour le type passé en paramètre en tenant
     * compte du statut des documents et des droits de l'utilisateur en cours.
     *
     * @param string $type
     */
    public function defaultFilter($type) {
        global $wp_post_statuses;

        // Définit des filtres sur le statut des notices en fonction
        // des droits de l'utilisateur :
        // - Tout le monde peut lire les statuts publics
        // - Si l'utilisateur est connecté, il peut voir les status
        //   privés s'il a le droit "read_private_posts" du type ou
        //   s'il est l'auteur de la notice ou du post.
        // Le code est inspiré de ce que fait WordPress quand on appelle
        // WP_Query::get_posts (cf. wp_includes/query.php:3027)

        $postType = get_post_type_object($type);
        $readPrivate = $postType->cap->read_private_posts;
        $canReadPrivate = current_user_can($readPrivate);

        // Détermine la liste des statuts publics et privés/protégés
        $public = $private = [];
        foreach($wp_post_statuses as $status) {
            if ($status->public) {
                $public[] = $status->label;
            } elseif($status->protected || $status->private) {
                $private[] = $status->label;
            }
        }

        // Si l'utilisateur a le droit "read_private_posts" : tout
        if ($canReadPrivate) {
            $filter = SearchRequest::termFilter('status.filter', $public + $private);
        }

        // Sinon, que le statut "publish"
        else {
            $filter = SearchRequest::termFilter('status.filter', $public);

            //  Et les statuts privés pour les posts dont il est auteur
            if ($private && is_user_logged_in()) {
                $user = wp_get_current_user()->user_login;

                $filter = SearchRequest::shouldFilter(
                    $filter,
                    SearchRequest::mustFilter(
                        SearchRequest::termFilter('createdby.filter', $user),
                        SearchRequest::termFilter('status.filter', $private)
                    )
                );
            }
        }

        // Combine en "et" avec le type
        return SearchRequest::mustFilter(SearchRequest::typeFilter($type), $filter);
    }

    /**
     * Retourne l'ID de la page "liste des réponses" indiquée dans les
     * paramètres de docalist-search.
     *
     * @return int
     */
    public function searchPage() {
        return $this->settings->searchpage();
    }

    /**
     * Retourne l'URL de la page "liste des réponses" indiquée dans les
     * paramètres de docalist-search.
     *
     * @return string
     */
    public function searchPageUrl() {
        return get_permalink($this->settings->searchpage());
    }

    /**
     * Retourne la requête en cours.
     *
     * @return SearchRequest
     */
    public function request() {
        return $this->request;
    }

    /**
     * Retourne les résultats de la requête en cours.
     *
     * @return SearchResults
     */
    public function results() {
        return $this->results;
    }

    /**
     * Retourne le rank d'un hit, c'est à dire la position de ce hit (1-based)
     * dans l'ensemble des réponses qui répondent à la requête.
     *
     * @param int $id
     *
     * @return int Retourne la position du hit dans les résultats (le premier
     * est à la position 1) ou zéro si l'id indiqué ne figure pas dans la liste
     * des réponses.
     */
    public function rank($id) {
        if ($this->results) {
            return $this->results->position($id) + 1 + ($this->request->page() - 1) * $this->request->size();
        }

        // Le hit demandé ne fait pas partie des réponses
        return 0; // // @todo null ? zéro ? exception ?
    }

    /**
     * Retourne le lien à utiliser pour afficher le hit indiqué tout seul sur
     * une page (i.e. recherche en format long).
     *
     * Le lien retourné est un lien qui permet de relancer une recherche avec
     * start=rank(id) et size=1
     *
     * @param int $id
     */
    public function hitLink($id) {
        $url = get_pagenum_link($this->rank($id), false);
        $url = add_query_arg(['size' => 1], $url);

        return $url;
    }

    /**
     * Filtre "parse_query" exécuté lorsque WordPress analyse la requête
     * adressée au site.
     *
     * Remplace la recherche standard de WordPress par notre moteur.
     *
     * Si la requête est une recherche, et qu'il s'agit de la requête
     * principale, on installe les filtres supplémentaires qui vont permettre
     * d'exécuter la recherche (onPostsRequest, onPostsResults, etc.)
     *
     * @param WP_Query $query La requête analysée par WordPress.
     *
     * @return WP_Query La requête, éventuellement modifiée.
     */
    public function onParseQuery(WP_Query & $query) {
        $debug = false;

        // Si ce n'est pas la requête principale de WordPress on ne fait rien
        if (! $query->is_main_query()) {
            return $query;
        }

        // Demande aux plugins s'il faut créer une requête
        $this->request = apply_filters('docalist_search_create_request', null, $query);

        // Si on n'a pas de requête à exécuter, on ne fait rien
        if (is_null($this->request)) {
            $debug && print("docalist_search_create_request a retourné null, rien à faire<br />");

            return $query;
        }

        // Sanity check
        if (! $this->request instanceof SearchRequest) {
            throw new Exception('Filter docalist_search_create_request did not return a SearchRequest');
        }

        $debug && print("docalist_search_create_request a retourné une requête, exécution<br />");

        // Si la requête est une recherche WordPress, on tient compte de "paged"
        if ($this->request->isSearch()) {
            if ($page = $query->get('paged')) {
                $this->request->page($page);
            } elseif ($page = $query->get('page')) {
                $this->request->page($page);
            }
        }

        $debug && var_dump($this->request);

        // Exécute la recherche
        try {
            $this->results = $this->request->execute();
        } catch (Exception $e) {
            echo "<p>WARNING : Une erreur s'est produite pendant l'exécution de la requête.</p>";
            echo '<p>', $e->getMessage(), '</p>';
            // TODO : à améliorer (cf. plugin "simple notices")
        }

        $debug && print($this->results->total() . " réponses obtenues<br />");

        // Si la requête n'est pas une recherche WordPress, on a finit
        if (! $this->request->isSearch()) {
            $debug && print("Le flag isSearch de la requête SearchRequest est à false, terminé<br />");

            return $query;
        }

        $debug && print("Le flag isSearch est à true, force WP à exécuter comme une recherche<br />");

        // Force WordPress à traiter la requête comme une recherche
        $query->is_search = true;
        $query->is_singular = $query->is_page = false;

        // Indique à WordPress les paramètres de la recherche en cours
        $query->set('posts_per_page', $this->request->size());
        $query->set('paged', $this->request->page());

        // Empêche WordPress de faire une 2nde requête "SELECT FOUND_ROWS()"
        // (inutile car on a directement le nombre de réponses obtenues)
        $query->set('no_found_rows', true);

        // Permet à get_search_query() de récupérer l'équation de recherche
        $query->set('s', $this->request->asEquation());

        // Construit la liste des ID des réponses obtenues
        $id = [];
        if ($this->results) {
            foreach($this->results->hits() as $hit) {
                $id[] = $hit->_id;
            }
        }

        // Indique à WordPress la requête SQL à exécuter pour récupérer les posts
        add_filter('posts_request', function ($sql) use ($id) { // !!! pas appellé si supress_filters=true
            global $wpdb; /* @var $wpdb Wpdb */

            // Aucun hit : retourne sql=null pour que wpdb::query() ne fasse aucune requête
            if (empty($id)) {
                return null;
            }

            // Construit une requête sql qui récupére les posts dans l'ordre
            // indiqué par ElasticSearch (http://stackoverflow.com/a/3799966)
            $sql = 'SELECT * FROM %s WHERE ID in (%s) ORDER BY FIELD(id,%2$s)';
            $sql = sprintf($sql, $wpdb->posts, implode(',', $id));

            // TODO : telle que la requête est construite, c'est forcément des
            // posts (pas des commentaires, ou des users, etc.)

            // TODO : supprimer le filtre une fois qu'il a été exécuté
            return $sql;
        });

        // Une fois que WordPress a chargé les posts, vérifie qu'on a tout les
        // documents et indique à WordPress le nombre total de réponses trouvées.
        add_filter('posts_results', function(array $posts = null, WP_Query & $query) use ($id) { //!!! pas appellé si supress_filters=true
            if (count($id) !== count($posts)) {
                echo "<p>WARNING : L'index docalist-search est désynchronisé.</p>";
                // TODO : à améliorer (cf. plugin "simple notices")
            }
            $total = $this->results ? $this->results->total() : 0;
            $size = $this->request->size();

            $query->found_posts = $total;
            $query->max_num_pages = (int) ceil($total / $size);

            // TODO : supprimer le filtre une fois qu'il a été exécuté

            return $posts;
        }, 10, 2);

        return $query;
    }

    /**
     * Recherche dans un champ tous les termes qui commencent par un préfixe
     * donné.
     *
     * @param string $source nom du champ index.
     * @param string $search préfixe recherché.
     *
     * @return string|array En cas de succès, retourne un tableau de termes.
     * Chaque terme est un objet contenant les clés "term" et "count". Par
     * exemple la recherche "NOT" sur un champ "auteur" pourrait retourner :
     * [
     *     {"term": "NOTAT (Nicole)", "score": 1 },
     *     {"term": "NOTHOMB (AMELIE)", "score": 3},
     * ]
     *
     * Si aucun terme ne commence par le préfixe indiqué, la méthode retourne
     * un tableau vide.
     */
    public function lookup($source, $search) {
        // Remarques :
        // 1. Pour le moment, le "completion suggester" ne permet pas de filtrer
        //    par type. Ce sera possible plus tard avec le "ContextSuggester".
        //    cf. https://github.com/elasticsearch/elasticsearch/issues/3958
        //    Les lookups faits sur un champ portent donc sur toutes les bases
        //    de données qui contiennent ce champ.
        // 2. Il n'est pas possible de lancer une recherche avec search='', ça
        //    retourne zéro réponses.
        // 3. Le mode "recherche par code" (avec search entre crochets) n'est
        //    pas supporté (les crochets sont supprimés).

        // On ne gère pas la recherche par code, ignore les crochets
        if (strlen($search) >= 2 && $search[0] === '[' && substr($search, -1) === ']') {
            $search = substr($search, 1, -1);
        }

        // Construit la requête Elastic Search

        if ($search === '') {
            $query = [
                'aggs' => [
                    'lookup' => [
                        "terms" => [
                            "field" => "$source.filter",
                            "size" => 100,
                            "order" => [ "_term" => "asc" ]
                        ]
                    ]
                ]
            ];

            // Exécute la requête
            $result = docalist('elastic-search')->post('/{index}/_search?search_type=count', $query);
            if (! isset($result->aggregations->lookup->buckets)) {
                return [];
            }

            $result = $result->aggregations->lookup->buckets;
            foreach($result as $bucket) {
                $bucket->text = $bucket->key;
                unset($bucket->key);

                $bucket->score = $bucket->doc_count;
                unset($bucket->doc_count);
            }

            return $result;
        }

        // @see http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/search-suggesters-completion.html
        $query = [
            'lookup' => [
                'text' => $search,
                'completion' => [
                    'field' => "$source.suggest",
                    'size' => 100,
                    // 'fuzzy' => true
                    'prefix_len' => 1,
                ]
            ]
        ];

        // Exécute la requête
        $result = docalist('elastic-search')->post('/{index}/_suggest', $query);

        // Récupère les suggestions
        if (! isset($result->lookup[0]->options)) {
            return [];
        }

        return $result->lookup[0]->options;
    }
}