<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2013 Daniel Ménard
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
     * Le serveur Elastic Search qui va exécuter la requête.
     *
     * @var ElasticSearch
     */
    protected $server;

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
     * Terme, expression ou équation de recherche
     *
     * @var string
     */
    protected $search;

    /**
     * Liste des filtres à appliquer à la requête
     *
     * @var array Un tableau de la forme filterName => value ou
     * filterName => array(value, value)
     */
    protected $filters = array();

    /**
     * Liste des facettes à calculer
     *
     * @var array Un tableau de la forme facetName => size
     */
    protected $facets = array();

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
     * Construit une recherche à partir des arguments passés en paramètre.
     *
     * Exemple :
     * <code>
     * $request = new SearchRequest(array(
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
     * ));
     * </code>
     *
     * @param ElasticSearch le serveur Elastic Search auquel sera envoyé la
     * requête.
     *
     * @param array $args un tableau contenant les paramètres de la recherche à
     * exécuter.
     */
    public function __construct(ElasticSearchClient $server, $args = null) {
        $this->server = $server;

        if ($args) {
            // Arguments dont le nom correspond à un setter de notre classe
            foreach (array('page', 'size', 'sort') as $arg) {
                isset($args[$arg]) && $this->$arg($args[$arg]);
            }

            // Arguments dont le nom diffère
            isset($args['s']) && $this->search($args['s']);

            // Arguments utilisables sans valeur
            isset($args['explain-hits']) && $this->explainHits(true);

            // Autres arguments : filtres et facettes
            foreach($args as $key => $value) {
                // Filtre de la forme filter.field=value
                if (strncmp($key, 'filter.', 7) === 0) {
                    $this->filter(substr($key, 7), $value);
                }

                // Facette de la forme facet.name=size
                elseif (strncmp($key, 'facet.', 6) === 0) {
                    $this->facet(substr($key, 6), $value);
                }

                // Facette indiquée comme valeur dans le tableau (size par défaut)
                elseif (is_string($value) && strncmp($value, 'facet.', 6) === 0) {
                    $this->facet(substr($value, 6), 10); // TODO: size par défaut de la facette
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
        if (is_null($size)) return $this->size;

        $size = (int) $size;
        if ($size < 1) {
            throw new Exception(__('Size incorrect', 'docalist-search'));
        }
        $this->size = $size;

        return $this;
    }

    /**
     * Retourne ou modifie le terme recherché (ou l'expression, ou l'équation).
     *
     * @param int $page
     * @return int|self
     */
    public function search($search = null) {
        if (is_null($search)) return $this->search;

        $this->search = $search;

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

        $this->filters = array();
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
     * @return null|string|self
     */
    public function filter($name, $value = null) {
        if (is_null($value)) {
            return isset($this->filters[$name]) ? $this->filters[$name] : null;
        }

        if (is_array($value)) {
            $this->filters[$name] = $value;
        } else {
            $this->filters[$name][] = $value;
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
        if (! isset($this->filters[$name])) {
            return false;
        }

        if (is_null($value)) {
            return true;
        }

        return in_array($value, $this->filters[$name], true);
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

        $this->facets = array();
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
        if (is_null($sort)) return $this->sort;

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
        if (is_null($explainHits)) return $this->explainHits;

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
        $request = array(
            'query' => $this->elasticSearchQuery(),
            'fields' => array(), // on ne veut que ID
        );

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
        // http://www.elasticsearch.org/guide/reference/api/search/query/
        $query = array(
            'query_string' => array(
                'query' => $this->search,
            )
        );

        // Filtres éventuels. La requête devient une "filtered-query"
        // http://www.elasticsearch.org/guide/reference/query-dsl/filtered-query/
        if ($filter = $this->elasticSearchFilter()) {
            $query = array(
                'filtered' => array(
                    'query' => $query,
                    'filter' => $filter
                )
            );
        }

        return $query;
    }

    /**
     * Crée la partie "filter" de la requête envoyée à Elastic Search.
     *
     * @return array
     */
    protected function elasticSearchFilter() {
        // Chaque filtre est un "terms filter"
        // http://www.elasticsearch.org/guide/reference/query-dsl/terms-filter/
        $filters = array();
        foreach ($this->filters as $name => $filter) {
            $filters[] = array(
                'terms' => array(
                    $name => $filter,
                    'execution' => 'and', // TODO: options
                ),
            );
        }

        // Si on a plusieurs filtres, on les combine en "ET"
        // http://www.elasticsearch.org/guide/reference/query-dsl/and-filter/
        count($filters) > 1 && $filters = array('and' => $filters);

        // TODO : est-ce qu'un "bool filter" serait mieux ou plus efficace ?
        // TODO : gérer la mise en cache des filtres

        return $filters;
    }

    /**
     * Crée la partie "facets" de la requête envoyée à Elastic Search.
     *
     * @return array
     */
    protected function elasticSearchFacets() {
        $definedFacets = apply_filters('docalist_search_get_facets', array());

        $facets = array();
        foreach ($this->facets as $name => $size) {
            if (! isset($definedFacets[$name])) {
                throw new Exception("La facette $name n'existe pas");
            }
            $facet = $definedFacets[$name];
            $type = isset($facet['type']) ? $facet['type'] : 'terms';
            $facets[$name] = array($type => $facet['facet']);
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
     * @return Results les résultats de la recherche (peuvent également être
     * obtenus ultérieurement en appellant results()).
     */
    public function execute() {
        $response = $this->server->get('_search', $this->elasticSearchRequest());

        return new Results($response);
    }

    /**
     * Retourne une équation de recherche indiquant comment ElasticSearch à
     * analysé la recherche saisie par l'utilisateur.
     *
     * @return string
     */
    public function explainQuery() {
        $response = $this->server->get('_validate/query?explain', $this->elasticSearchQuery());

        return $response->explanations[0];
    }
}