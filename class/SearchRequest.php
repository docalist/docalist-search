<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2013-2015 Daniel Ménard
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
use Docalist;
use Exception;

/**
 * Une requête de recherche adressée à ElasticSearch.
 */
class SearchRequest {
    /**
     * Numéro de la page de résultats à retourner (1-based)
     *
     * @var int
     */
    protected $page = 1;

    /**
     * Nombre de réponses par page
     *
     * @var int
     */
    protected $size = 10;

    /**
     * Liste des clauses de recherche.
     *
     * @var array un tableau de la forme "nom de champ" => array(equations)
     */
    protected $search = [];

    /**
     * Liste des filtres utilisateur à appliquer à la requête.
     *
     * @var array Un tableau de la forme filterName => value ou
     * filterName => array(value, value)
     */
    protected $filters = [];

    /**
     * Liste des filtres "cachés" (non affichés à l'utilisateur).
     *
     * Typiquement, il s'agit de filtres par défaut portant sur le statut des
     * posts (par exemple status.filter:Publié).
     *
     * Contrairement à $filters qui contient des filtres sous la forme
     * d'équations de recherche qui sont ensuite parsées, $hiddenFilters
     * contient directement les filtres au format elastic search (query DSL).
     *
     * @var array
     */
    protected $hiddenFilters = [];

    /**
     * Liste des facettes à calculer
     *
     * @var array Un tableau de la forme facetName => size
     */
    protected $facets = [];

    /**
     * Ordre de tri des résultats
     *
     * @var array
     */
    protected $sort;

    /**
     * Demande à ES de fournir une explication sur chacun des hits obtenus.
     *
     * @var bool
     */
    protected $explainHits = false;

    /**
     * Indique si la requête doit être traitée comme s'il s'agissait d'une
     * recherche WordPress.
     *
     * Lorsque ce flag est à true, docalist-search force WordPress à traiter la
     * requête en cours comme s'il s'agissait d'une recherche (le flag is_search
     * de wp_query est positionné à true, le template search.php sera utilisé, etc.)
     *
     * @var bool
     */
    protected $isSearch = false;

    /**
     * Construit une recherche à partir des arguments passés en paramètre.
     *
     * Exemple :
     * <code>
     * $request = new SearchRequest([
     *     'page' => 2,
     *     'size' => 10,
     *     'search' => 'author:picasso AND title:"demoiselles avignon"',
     *     'filter.type' => 'tableau',
     *     'filter.keyword' => array('peinture', 'art'),
     *     'facet.keyword' => 10,
     *     'facet.journal' => 20,
     *     'facet._type',
     *     'explain-hits' => true,
     *     'sort' => 'date- _score',
     * ]);
     * </code>
     *
     * @param array $args un tableau contenant les paramètres de la recherche à
     * exécuter.
     */
    public function __construct($args = null) {
        if ($args) {
            // Arguments dont le nom correspond à un setter de notre classe
            foreach (['page', 'size', 'sort'] as $arg) {
                if (isset($args[$arg])) {
                    $this->$arg($args[$arg]);
                    unset($args[$arg]);
                }
            }

            // Arguments dont le nom diffère
            if (isset($args['q'])) {
                $q = trim($args['q']);
                if (!empty($q) && $q !== '*') {
                    $this->search('', $q);
                }
                unset($args['q']);
            }

            // Arguments utilisables sans valeur
            if (isset($args['explain-hits'])) {
                $this->explainHits(true);
                unset($args['explain-hits']);
            }

            if (isset($args['explain-query'])) {
                unset($args['explain-query']);
            }

            // Filtres connus
            foreach (['_type'] as $arg) {
                if (isset($args[$arg]) && !empty($args[$arg])) {
                    if ($args[$arg] !== 'any') {
                        $this->filter($arg, $args[$arg]);
                    }
                }
                unset($args[$arg]);
            }

            // Autres arguments : filtres et facettes
            foreach($args as $key => $value) {
                //$value = trim($value);
                if ($key && $key[0] === '_') {
                    continue;
                }

                if (empty($value)) {
                    continue;
                }

                // Filtre de la forme filter.field=value
                if (substr($key, -7) === '_filter') { // .filter mais php transforme les "." en"_"
                    $key = substr($key, 0, -7) . '.filter';
                    $this->filter($key, $value);
                }

                // Facette de la forme facet.name=size
                elseif (strncmp($key, 'facet.', 6) === 0) {
                    $this->facet(substr($key, 6), $value);
                }

                // Facette indiquée comme valeur dans le tableau (size par défaut)
                elseif (is_string($value) && strncmp($value, 'facet.', 6) === 0) {
                    $this->facet(substr($value, 6), 10); // TODO: size par défaut de la facette
                }

                // Nom de champ quelconque
                else {
                    $this->search($key, $value);
                }
            }
        }
    }

    /**
     * Retourne ou modifie le numéro de la page de résultats à retourner (1-based)
     *
     * @param int $page
     * @return int|self
     */
    public function page($page = null) {
        if (is_null($page)) return $this->page;

        $page = (int) $page;
        if ($page < 1) {
            throw new Exception(__('Page incorrecte', 'docalist-search'));
        }
        $this->page = $page;

        return $this;
    }

    /**
     * Retourne ou modifie le nombre de résultats par page (10 par défaut)
     *
     * @param int $page
     * @return int|self
     */
    public function size($size = null) {
        if (is_null($size)) {
            return $this->size;
        }

        $size = (int) $size;
        if ($size < 1) {
            throw new Exception(__('Size incorrect', 'docalist-search'));
        }
        $this->size = $size;

        return $this;
    }

    /**
     * Ajoute une clause de recherche ou retourne toutes les clauses.
     *
     * @param string|string[] $search Une ou plusieurs équations de recherche.
     * @param string $field Le nom du champ de recherche.
     *
     * @return array|self
     */
    public function search($field = null, $search = null) {
        if (is_null($search)) {
            return $this->search;
        }

        ! isset($this->search[$field]) && $this->search[$field] = [];

        foreach((array) $search as $search) {
            $this->search[$field][] = $search;
        }

        return $this;
    }

    /**
     * Retourne ou modifie la liste des filtres appliqués à la requête.
     *
     * @param null|array $filters
     * @return array|self
     */
    public function filters($filters = null) {
        if (is_null($filters)) {
            return $this->filters;
        }

        $this->filters = [];
        foreach($filters as $name => $value) {
            $this->filter($name, $value);
        }

        return $this;
    }

    /**
     * Ajoute ou retourne un filtre.
     *
     * @param string $name Nom du filtre
     * @param string $value Valeur
     *
     * @return null|array|self
     */
    public function filter($name, $value = null) {
        if (is_null($value)) {
            return isset($this->filters[$name]) ? $this->filters[$name] : null;
        }

        foreach((array) $value as $value) {
            if (empty($value)) {
                continue;
            }

            ! isset($this->filters[$name]) && $this->filters[$name] = [];

            foreach(explode(',', $value) as $item) {
                $this->filters[$name][$item] = true;
            }
        }

        return $this;
    }

    /**
     * Indique si la requête contient le filtre indiqué.
     *
     * @param string $name
     * @param string $value
     *
     * @return boolean
     */
    public function hasFilter($name, $value = null) {
        return is_null($value) ? isset($this->filters[$name]) : isset($this->filters[$name][$value]);
    }

    public function clearFilter($name, $value = null) {
        if (is_null($value)) {
            unset($this->filters[$name]);
        } elseif (isset($this->filters[$name])) {
            unset($this->filters[$name][$value]);
            if (empty($this->filters[$name])) {
                unset($this->filters[$name]);
            }
        }

        return $this;
    }

    public function toggleFilter($name, $value) {
        if (isset($this->filters[$name][$value])) {
            unset($this->filters[$name][$value]);
            if (empty($this->filters[$name])) {
                unset($this->filters[$name]);
            }
        } else {
            $this->filters[$name][$value] = true;
        }

        return $this;
    }

    public function hiddenFilters() {
        return $this->hiddenFilters;
    }

    public function addHiddenFilter($filter) {
        $this->hiddenFilters[] = $filter;

        return $this;
    }

    /**
     * Retourne ou modifie la liste des facettes de la requête.
     *
     * @param null|array $facets
     * @return array|self
     */
    public function facets($facets = null) {
        if (is_null($facets)) {
            return $this->facets;
        }

        $this->facets = [];
        foreach($facets as $name => $size) {
            $this->facet($name, $size);
        }

        return $this;
    }

    /**
     * Ajoute ou retourne une facette.
     *
     * @param string $name
     * @param int $size
     *
     * @return null|int
     */
    public function facet($name, $size = null) {
        if (is_null($size)) {
            return isset($this->facets[$name]) ? $this->facets[$name] : null;
        }

        $this->facets[$name] = $size;

        return $this;
    }

    /**
     * Indique si la requête contient le facette indiquée.
     *
     * @param string $name
     * @param string $value
     *
     * @return boolean
     */
    public function hasFacet($name) {
        if (! isset($this->facets[$name])) {
            return false;
        }

        if ($this->facets[$name] === 0) {
            return false;
        }

        return true;
    }

    /**
     * Retourne ou modifie l'ordre de tri
     *
     * @param string $sort
     * @return string|self
     */
    public function sort($sort = null) {
        if (is_null($sort)) {
            return $this->sort;
        }

        $this->sort = $sort;

        return $this;
    }

    /**
     * Retourne ou modifie l'option "expliquer les hits".
     *
     * @param bool $explainHits
     * @return bool|self
     */
    public function explainHits($explainHits = null) {
        if (is_null($explainHits)) {
            return $this->explainHits;
        }

        $this->explainHits = (bool) $explainHits;

        return $this;
    }

    /**
     * Crée la requête à envoyer à Elastic Search à partir des paramètres en
     * cours.
     *
     * @return array
     */
    protected function elasticSearchRequest() {
        // Paramètres de base de la requête
        $request = [
            'query' => $this->elasticSearchQuery(),
            'fields' => [] // on ne veut que ID
        ];

        // Tri des réponses
        // TODO

        // Nombre de réponses par page
        if( $this->size !== 10) {
            $request['size'] = $this->size;
        }

        // Numéro du premier hit
        if ($this->page > 1) {
            $request['from'] = ($this->page - 1) * $this->size;
        }

        // Expliquer les hits obtenus
        if ($this->explainHits) {
            $request['explain'] = true;
        }

        // Facettes éventuelles
        if ($facets = $this->elasticSearchFacets()) {
            $request['facets'] = $facets;
        }

        return $request;
    }

    /**
     * Crée la partie "query" de la requête envoyée à Elastic Search.
     *
     * La clause retournée contient à la fois la recherche de l'utilisateur
     * et les filtres appliqués à la requête.
     *
     * @return array
     */
    protected function elasticSearchQuery() {
        // @see http://www.elasticsearch.org/guide/reference/api/search/query/
        $query = [];
        foreach($this->search as $field => $search) {
            $clauses = []; // les clauses de recherche pour ce champ

            // $field peut être :
            // - une chaine vide = recherche "tous champs" (_all)
            // - un nom de champ unique (e.g. topic=social)
            // - plusieurs noms de champs séparés par une virgule (title,translation=social)
            // - un ensemble de champs (topic.*=social)
            if (empty($field)) {
                // @todo : filtre docalist_search_get_default_fields

                // Pour les notices :
                $field = 'type,genre,media,title,othertitle,translation,author,organisation,date,journal,number,editor,collection,event,content,topic';

                // number.issn : ES n'applique pas les mappings (créo* -> le "é" n'est pas remplacé par "e")
                // donc au final, tout number par défaut

                // Pour les posts et les pages
                // $field .= ',title,content'; // champs déjà présents pour "notices"
            }

            foreach((array) $search as $search) {

                // QueryString Query
                $clause = [
                    'query_string' => [
                        // Equation de recherche
                        'query' => $this->escapeQuery($search),

                        // Champ(s) par défaut
                        'fields' => explode(',', $field),

                        // Opérateur (cf. #246)
                        'default_operator' => 'AND',
                        // 'minimum_should_match' => '75%',

                        // Force les troncatures à passer par l'analyzeur du champ
                        'analyze_wildcard' => true,

                        //'use_dis_max' => false,

                        //'auto_generate_phrase_queries' => true,

                        // Evite certaines erreurs
                        'lenient' => true,
                    ]
                ];

                // Field Query
/*
                $clause = [
                    'field' => [
                        $field => [
                            'query' => $search,
                            'analyze_wildcard' => true,
                            'minimum_should_match' => '100%',
                            'lenient' => true,
                        ]
                    ]
                ];
*/
                $clauses[] = $clause;
            }
            if (count($clauses) > 1) {
                $clauses = ['bool' => ['should' => $clauses]];
            } else {
                $clauses = $clauses[0];
            }

            $query[] = $clauses;
        }

        switch (count($query)) {
            case 0:
                $query = null;
                break;

            case 1:
                $query = $query[0];
                break;

            default:
                $query = ['bool' => ['must' => $query]];
                break;
        }

        // Filtres éventuels. La requête devient une "filtered-query"
        // http://www.elasticsearch.org/guide/reference/query-dsl/filtered-query/
        if ($filter = $this->elasticSearchFilter()) {
            if ($query) {
                $query = [
                    'filtered' => [
                        'query' => $query,
                        'filter' => $filter
                    ]
                ];
            } else {
                $query = [
                    'filtered' => [
                        'filter' => $filter
                    ]
                ];
            }
        }

        if (is_null($query)) {
            $query = ['match_all' => []];
        }

        return $query;
    }

    protected function escapeQuery($query) {
        return strtr($query, [
            '/' => '\/',
        ]);
    }

    /**
     * Crée la partie "filter" de la requête envoyée à Elastic Search.
     *
     * @return array
     */
    protected function elasticSearchFilter() {
        // Ajoute tous les filtres cachés
        $filters = $this->hiddenFilters;

        // AJouite les filtres utilisateurs
        foreach ($this->filters as $name => $value) {
            if (count($value) === 1) {
                $filter = ['term' => [$name => key($value)]];
            } else {
                $value = array_keys($value);
                if ($name === '_type') {
                    $filter = ['terms' => [$name => $value]]; // execution: or par défaut
                } else {
                    $filter = ['terms' => [$name => $value, 'execution' => 'and']];
                }
            }
            $filters[] = $filter;

//             $op = $name==='_type' ? 'or' : 'and';  // TODO: options
//             $filters[] = [
//                 'terms' => [
//                     $name => $value,
//                     'execution' => $op,
//                 ],
//             ];
        }

        // Si on a plusieurs filtres, on les combine en "ET". On utilise un
        // "bool filter" plutôt qu'un "and filter" car c'est plus efficace.
        // cf. https://www.elastic.co/blog/all-about-elasticsearch-filter-bitsets
        count($filters) > 1 && $filters = ['bool' => ['must' => $filters]];

        return $filters;
    }

    /**
     * Crée la partie "facets" de la requête envoyée à Elastic Search.
     *
     * @return array
     */
    protected function elasticSearchFacets() {
        $definedFacets = apply_filters('docalist_search_get_facets', []);

        $facets = [];
        foreach ($this->facets as $name => $size) {
            if (! isset($definedFacets[$name])) {
                throw new Exception("La facette $name n'existe pas");
            }
            $facet = $definedFacets[$name];
            $type = isset($facet['type']) ? $facet['type'] : 'terms';
            $facets[$name] = [$type => $facet['facet']];
        }
//        echo 'elasticSearchFacets=<pre>', var_export($facets, true), '</pre>';
        return $facets;
/*

        return array(
            'ref.type' => array(
                'state' => 'normal',
                'label' => __('Type de document', 'docalist-biblio'),
                'type'  => 'terms',
                'facet' => array(
                    'field' => 'type.keyword',
                )
            )
        );

 */
/*
        $facets = Docalist::get('docalist-search')->facets();
        $result = array();
        foreach($facets as $key => $facet) {
            $state = isset($facet['state']) ? $facet['state'] : 'normal';
            $type = isset($facet['type']) ? $facet['type'] : 'terms';
            if (! isset($facet['facet']) || ! is_array($facet['facet'])) {
                throw new Exception("La facette $key doit indiquer une clé 'facet' contenant un tableau.");
            }

            switch($state) {
                case 'hidden':    // Ne calculer la facette (et ne l'afficher) que si elle est demandée en query string
                    if (! $this->hasFacet($key)) {
                        break; // exit si elle n'a pas été indiqué en query string
                    }
                case 'normal':    // Calcule la facette, affiche le titre et les valeurs
                case 'collapsed': // Calcule la facette, affiche le titre, les valeurs sont en display:none
                    $result[$key] = array(
                        $type => $facet['facet']
                    );
                    break;
                case 'closed':    // Ne pas calculer la facette, affiche le titre, ajoute la facette en query string quand l'utilisateur clique
                    break;
                default:
                    throw new Exception("Valeur incorrecte pour la clé 'state' de la facette $key : $state.");
            }
        }

        return $result;
*/
    }

    /**
     * Envoie la requête au serveur ElasticSearch passé en paramètre et stocke
     * la réponse obtenue.
     *
     * @return SearchResults les résultats de la recherche (peuvent également
     * être obtenus ultérieurement en appellant results()).
     */
    public function execute($searchType = null) {
        $es = docalist('elastic-search');
        $searchType && $searchType = "?search_type=$searchType";
        $response = $es->get("/{index}/_search$searchType", $this->elasticSearchRequest());
        if (isset($response->error)) {
            throw new Exception($response->error);
        }

        return new SearchResults($response, $es->time());
    }

    /**
     * Retourne une équation de recherche indiquant comment ElasticSearch à
     * analysé la recherche saisie par l'utilisateur.
     *
     * @return string
     */
    public function explainQuery() {
        $query = ['query' => $this->elasticSearchQuery()];
        $response = docalist('elastic-search')->get('/{index}/_validate/query?explain', $query);

        return $response->explanations[0];
    }

    protected function addBrackets(& $term, $c = null) {
        if (false === strpos($term, ' ')) {
            return;
        }

        // expression entre guillemets
        if (preg_match('~^"[^"]*"$~', $term)) {
            return;
        }

        // range
        if (preg_match('~^\[.*\]$~', $term)) {
            return;
        }

        $term = $c ? "($c $term $c)" : '(' . $term . ')';
    }

    public function asEquation() {
        if (empty($this->search)) {
            return '*';
        }

        $equation = [];
        foreach($this->search as $field => $search) {
            $clause = implode(' OR ', (array) $search);
            if ($field) {
                $clauses = [];
                $this->addBrackets($clause);
                foreach(explode(',', $field) as $field) {
                    $clauses[] = "$field:$clause";
                }
                if (count($clauses) > 1) {
                    $clause = '(' . implode(' OR ', $clauses) . ')';
                } else {
                    $clause = $clauses[0];
                }
            }
            $equation[] = $clause;
        }
        $equation = implode(' AND ', $equation);

        return $equation;
    }

    /**
     * Retourne l'url à utiliser pour activer ou désactiver un filtre.
     *
     * Si la requête en cours contient déjà le filtre indiqué (couple
     * $name/$value) celui-ci est supprimé de l'url. Dans le cas contraire, le
     * filtre est ajouté dans les paramères de l'url.
     *
     * supprime le filtre et retourne l'url obtenue.
     * Si le filtre ne figure pas dans la requête, on l'ajoute
     *
     * @param string $name Nom du filtre à activer ou à désactiver.
     * @param string $value Valeur à activer ou à désactiver.
     * @param string $url Url à modifier (par défaut la méthode utilise l'url
     * en cours retournée par get_pagenum_link(1, false)).
     *
     * @return string L'url obtenue.
     */
    public function toggleFilterUrl($name, $value, $url = null) {
        is_null($url) && $url = get_pagenum_link(1, false);
        if (isset($this->filters[$name][$value])) {
            $filter = $this->filters[$name];
            unset($filter[$value]);
        } else {
            $filter = isset($this->filters[$name]) ? $this->filters[$name] : [];
            $filter[$value] = true;
        }
        $filter = $filter ? urlencode(implode(',', array_keys($filter))) : false;

        return add_query_arg(strtr($name, '.', '_'), $filter, $url);
    }

    /**
     * Indique si la requête doit être traitée comme s'il s'agissait d'une
     * recherche WordPress.
     *
     * Lorsque ce flag est à true, docalist-search force WordPress à traiter la
     * requête en cours comme s'il s'agissait d'une recherche (le flag is_search
     * de wp_query est positionné à true, le template search.php sera utilisé,
     * etc.)
     *
     * Ce flag n'a de sens que pour la SearchRequest principale.
     *
     * @param boolean $isSearch
     * @return boolean|self
     */
    public function isSearch($isSearch = null) {
        if (is_null($isSearch)) {
            return $this->isSearch;
        }

        $this->isSearch = (bool) $isSearch;

        return $this;
    }
}