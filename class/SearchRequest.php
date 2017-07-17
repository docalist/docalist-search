<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2013-2017 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search;

use Docalist\Search\QueryDSL;
use InvalidArgumentException;

/**
 * Une requête de recherche adressée à ElasticSearch.
 */
class SearchRequest
{
    /**
     * Numéro de la page de résultats par défaut.
     *
     * @var int
     */
    const DEFAULT_PAGE = 1;

    /**
     * Nombre de réponses par page par défaut.
     *
     * @var int
     */
    const DEFAULT_SIZE = 10;

    /**
     * Liste des contenus sur lesquels portera la recherche.
     *
     * @var string[]
     */
    protected $types;

    /**
     * Numéro de la page de résultats à retourner (1-based).
     *
     * @var int
     */
    protected $page = self::DEFAULT_PAGE;

    /**
     * Nombre de réponses par page.
     *
     * @var int
     */
    protected $size = self::DEFAULT_SIZE;

    /**
     * Liste des requêtes qui composent la recherche.
     *
     * @var array
     */
    protected $queries = [];

    /**
     * Liste des filtres utilisateur à appliquer à la requête.
     *
     * @var array Un tableau de la forme filters[$name][$value] = true
     * (i.e. les filtres sont indexés par nom, puis par valeur).
     */
    protected $filters = [];

    /**
     * Liste des post-filters à appliquer à la requête.
     *
     * @var array Un tableau de la forme filters[$name][$value] = true
     * (i.e. les filtres sont indexés par nom, puis par valeur).
     */
    protected $postFilters = [];

    /**
     * Liste des filtres globaux appliqués à la recherche.
     *
     * @var array
     */
    protected $globalFilters = [];

    /**
     * Liste des clauses de tri.
     *
     * @var array
     */
    protected $sort;

    /**
     * Contrôle la liste des champs qui seront retournés pour chaque hit.
     *
     * @var bool|string|array
     */
    protected $sourceFilter = false;

    /**
     * Liste des agrégations qui composent la recherche.
     *
     * @var array
     */
    protected $aggregations = [];

    /**
     * Indique si la requête exécutée a des erreurs.
     *
     * Initialisé lorsque execute() est appelée.
     *
     * @var bool
     */
    protected $hasErrors = false;

    /**
     * Représentation de la requête sous forme d'équation de recherche.
     *
     * @var string
     */
    protected $equation;

    /**
     * L'objet SearchUrl qui a généré cette requête.
     *
     * @var SearchUrl
     */
    protected $searchUrl;

    // -------------------------------------------------------------------------------
    // Constructeur
    // -------------------------------------------------------------------------------

    /**
     * Construit une nouvelle requête de recherche.
     *
     * @param array $types Optionnel, liste des contenus sur lesquels portera la recherche (par défaut, tous).
     */
    public function __construct(array $types = [])
    {
        $this->setTypes($types);
    }

    // -------------------------------------------------------------------------------
    // Types de contenus
    // -------------------------------------------------------------------------------

    /**
     * Retourne la liste des types de contenus sur lesquels porte la recherche.
     *
     * @return string[]
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * Définit la liste des contenus sur lesquels porte la recherche.
     *
     * @param array $types
     *
     * @return self
     */
    public function setTypes(array $types = [])
    {
        if (empty($types)) {
            $indexManager = docalist('docalist-search-index-manager'); /** @var IndexManager $indexManager */
            $types = $indexManager->getTypes();
        }

        $this->types = $types;

        return $this;
    }

    // -------------------------------------------------------------------------------
    // SearchUrl
    // -------------------------------------------------------------------------------

    /**
     * Retourne l'objet SearchUrl qui a créé cette requête.
     *
     * @return SearchUrl Retourne null si setSearchUrl() n'a jamais été appellée.
     */
    public function getSearchUrl()
    {
        return $this->searchUrl;
    }

    /**
     * Définit l'objet SearchUrl qui a créé cette requête.
     *
     * @param SearchUrl $searchUrl
     *
     * @return self
     */
    public function setSearchUrl(SearchUrl $searchUrl)
    {
        $this->searchUrl = $searchUrl;

        return $this;
    }

    // -------------------------------------------------------------------------------
    // Equation
    // -------------------------------------------------------------------------------

    /**
     * Retourne une représentation de la recherche en cours sous la forme d'une équation de recherche.
     *
     * @return string
     */
    public function getEquation()
    {
        return $this->equation;
    }

    /**
     * Définit la représentation de la recherche en cours sous la forme d'une équation de recherche.
     *
     * @param string $equation
     *
     * @return self
     */
    public function setEquation($equation)
    {
        $this->equation = $equation;

        return $this;
    }

    // -------------------------------------------------------------------------------
    // Infos
    // -------------------------------------------------------------------------------

    /**
     * Indique si la recherche est vide
     *
     * La recherche est considérée comme vide si elle ne contient :
     * - aucune requête,
     * - aucun filtre utilisateur,
     * - pas de filtre global.
     *
     * Les agrégations éventuelles et les autres paramètres de la recherche (size, page...) ne sont pas
     * pris en compte.
     *
     * @return bool
     */
    public function isEmptyRequest()
    {
        return !$this->hasQueries() && !$this->hasFilters() && !$this->hasGlobalFilters();
    }

    // -------------------------------------------------------------------------------
    // Numéro de page
    // -------------------------------------------------------------------------------

    /**
     * Retourne le numéro de la page de résultats (1 par défaut).
     *
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Modifie le numéro de la page de résultats.
     *
     * Les numéros de page commencent à 1.
     *
     * @param int $page
     *
     * @return self
     */
    public function setPage($page)
    {
        $page = (int) $page;
        if ($page < 1) {
            throw new InvalidArgumentException('Incorrect page');
        }
        $this->page = $page;

        return $this;
    }

    // -------------------------------------------------------------------------------
    // Taille des pages
    // -------------------------------------------------------------------------------

    /**
     * Retourne le nombre de résultats par page (10 par défaut).
     *
     * @return int Un entier >= 0
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Modifie le nombre de résultats par page.
     *
     * @param int $size Un entier >= 0.
     *
     * @return self
     */
    public function setSize($size)
    {
        $size = (int) $size;
        if ($size < 0) {
            throw new InvalidArgumentException('Incorrect size');
        }
        $this->size = $size;

        return $this;
    }

    // -------------------------------------------------------------------------------
    // Queries
    // -------------------------------------------------------------------------------

    /**
     * Indique si la recherche contient des requêtes.
     *
     * @return bool
     */
    public function hasQueries()
    {
        return !empty($this->queries);
    }

    /**
     * Retourne les requêtes qui composent la recherche.
     *
     * @return array[]
     */
    public function getQueries()
    {
        return $this->queries;
    }

    /**
     * Définit la liste des requêtes qui composent la recherche.
     *
     * @param array[] $queries Un tableau de requêtes.
     *
     * Chaque requête est elle-même un tableau, en général créée avec le service QueryDSL. Exemple :
     *
     * <code>
     * $request->setQueries([
     *     $dsl->match('title', 'hello'),
     *     $dsl->match('content', 'world'),
     * ]);
     * </code>
     *
     * Si la méthode est appelée sans arguments ou avec un tableau vide, la liste des requêtes est réinitialisée.
     *
     * @return self
     */
    public function setQueries(array $queries = [])
    {
        $this->queries = [];
        foreach($queries as $query) {
            $this->addQuery($query);
        }

        return $this;
    }

    /**
     * Ajoute une requête à la liste des requêtes qui composent la recherche.
     *
     * Requêtes nommées : si la requête contient un paramètre "_name", elle est indexée par nom (le nom doit
     * être unique) et peut être manipulée en appellant hasQuery(), getQuery() ou removeQuery().
     *
     * @param array $query Un tableau décrivant la requête, en général créé avec le service QueryDSL. Exemple :
     *
     * <code>
     * $request->addQuery($dsl->match('title', 'hello world!')
     * </code>
     *
     * @return self
     */
    public function addQuery(array $query)
    {
        $name = $this->getQueryName($query);
        if (is_null($name)) {
            $this->queries[] = $query;
        } else {
            if (isset($this->queries[$name])) {
                throw new InvalidArgumentException("A query named '$name' already exists");
            }
            $this->queries[$name] = $query;
        }

        return $this;
    }

    /**
     * Indique si la recherche contient la requête indiquée.
     *
     * @param string|array $query Le nom de la requête à tester ou un tableau décrivant la requête.
     *
     * @return bool
     */
/*
    public function hasQuery($query)
    {
        if (is_string($query)) {
            return isset($this->queries[$query]);
        }

        return in_array($query, $this->queries, true);
    }
*/

    /**
     * Retourne la requête nommée dont le nom est indiqué.
     *
     * @param string $name Le nom de la requête à retourner.
     *
     * @return array|null Retourne la requête demandée ou null si aucune requête n'a le nom indiqué.
     */
/*
    public function getQuery($name)
    {
        return isset($this->queries[$name]) ? $this->queries[$name] : null;
    }
*/

    /**
     * Supprime la requête passée en paramétre.
     *
     * Remarque : aucune erreur n'est générée si la requête indiquée n'existe pas.
     *
     * @param string|array $query Le nom de la requête nommée à supprimer ou un tableau décrivant la requête.
     *
     * @return self
     */
/*
    public function removeQuery($query)
    {
        if (is_string($query)) {
            unset($this->queries[$query]);
        }

        elseif (false !== $key = array_search($query, $this->queries, true)) {
            unset($this->queries[$key]);
        }

        return $this;
    }
*/
    // -------------------------------------------------------------------------------
    // Filtres utilisateurs
    // -------------------------------------------------------------------------------

    /**
     * Indique si la recherche contient des filtres utilisateurs.
     *
     * @return bool
     */
    public function hasFilters()
    {
        return !empty($this->filters);
    }

    /**
     * Retourne les filtres utilisateurs qui composent la recherche.
     *
     * @return array[]
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Définit les filtres utilisateurs qui composent la recherche.
     *
     * @param array[] $filters Un tableau de filtres.
     *
     * Chaque filtre est lui-même un tableau, en général créé avec le service QueryDSL. Exemple :
     *
     * <code>
     * $request->setFilters([
     *     $dsl->term('type', 'post'),
     *     $dsl->term('status', 'publish'),
     * ]);
     * </code>
     *
     * Si la méthode est appelée sans arguments ou avec un tableau vide, la liste des filtres utilisateurs
     * est réinitialisée.
     *
     * @return self
     */
    public function setFilters(array $filters = [])
    {
        $this->filters = [];
        foreach($filters as $filter) {
            $this->addFilter($filter);
        }

        return $this;
    }

    /**
     * Ajoute un filtre utilisateur à la recherche.
     *
     * Filtres nommés : si le filtre contient un paramètre "_name", il est indexé par nom (le nom doit être unique)
     * et peut ensuite être manipulé en appellant hasFilter(), getFilter(), removeFilter().
     *
     * @param array $query Un tableau décrivant le filtre, en général créé avec le service QueryDSL. Exemple :
     *
     * <code>
     * $request->addFilter($dsl->term('status', 'publish')
     * </code>
     *
     * @return self
     */
    public function addFilter(array $filter)
    {
        $name = $this->getQueryName($filter);
        if (is_null($name)) {
            $this->filters[] = $filter;
        } else {
            if (isset($this->filters[$name])) {
                throw new InvalidArgumentException("A filter named '$name' already exists");
            }
            $this->filters[$name] = $filter;
        }

        return $this;
    }

    /**
     * Ajoute un post-filter à la recherche.
     *
     * @param array $query Un tableau décrivant le filtre, en général créé avec le service QueryDSL. Exemple :
     *
     * @return self
     */
    public function addPostFilter(array $filter)
    {
        $name = $this->getQueryName($filter);
        if (is_null($name)) {
            $this->postFilters[] = $filter;
        } else {
            if (isset($this->postFilters[$name])) {
                throw new InvalidArgumentException("A post filter named '$name' already exists");
            }
            $this->postFilters[$name] = $filter;
        }

        return $this;

    }

    /**
     * Indique si la recherche contient le filtre utilisateur indiqué.
     *
     * @param string|array $filter Le nom du filtre nommé à tester ou un tableau décrivant le filtre.
     *
     * @return bool
     */
/*
    public function hasFilter($filter)
    {
        if (is_string($filter)) {
            return isset($this->filters[$filter]);
        }

        return in_array($filter, $this->filters, true);

    }
*/

    /**
     * Retourne le filtre nommé dont le nom est indiqué.
     *
     * @param string $name Le nom du filtre à retourner.
     *
     * @return array|null Retourne le filtre demandé ou null si aucun filtre n'a le nom indiqué.
     */
/*
    public function getFilter($name)
    {
        return isset($this->filters[$name]) ? $this->filters[$name] : null;
    }
*/

    /**
     * Supprime le filtre utilisateur passé en paramètre.
     *
     * Remarque : aucune erreur n'est générée si la filtre indiqué n'existe pas.
     *
     * <code>
     * $request->removeFilter($dsl->term('status', 'publish');
     * $request->removeFilter('status-publish');
     * </code>
     *
     * @param string|array $filter Le nom du filtre nommé à supprimer ou un tableau décrivant le filtre.
     *
     * @return self
     */
/*
    public function removeFilter($filter)
    {
        if (is_string($filter)) {
            unset($this->filters[$filter]);
        }

        elseif (false !== $key = array_search($filter, $this->filters, true)) {
            unset($this->filters[$key]);
        }

        return $this;
    }
*/

    /**
     * Inverse le filtre utilisateur passé en paramètre.
     *
     * Si le filtre existe déjà dans la recherche, il est supprimé, sinon, il est ajouté.
     *
     * @param array $query Un tableau décrivant le filtre. Exemple :
     *
     * <code>
     * $request->toggleFilter($dsl->term('status', 'publish')
     * </code>
     *
     * return self
     */
/*
    public function toggleFilter(array $filter)
    {
        return $this->hasFilter($filter) ? $this->removeFilter($filter) : $this->addFilter($filter);
    }
*/
    // -------------------------------------------------------------------------------
    // Filtre global (caché)
    // -------------------------------------------------------------------------------

// old
    /**
     * Indique si la recherche contient des filtres globaux.
     *
     * @return bool
     */
    public function hasGlobalFilters()
    {
        return !empty($this->globalFilter);
    }

    /**
     * Retourne les filtres globaux appliqués à la recherche.
     *
     * @return array|null
     */
    public function getGlobalFilters()
    {
        return $this->globalFilters;
    }

    /**
     * Définit les filtres globaux appliqués à la recherche.
     *
     * @param array $filters Un tableau de filtres.
     *
     * Si la méthode est appelée avec un tableau vide, les filtres globaux sont réinitialisés.
     *
     * @return self
     */
    public function setGlobalFilters(array $filters = [])
    {
        $this->globalFilters = [];
        foreach($filters as $filter) {
            $this->addGlobalFilter($filter);
        }

        return $this;
    }

    /**
     * Ajoute un filtre global à la recherche.
     *
     * Filtres nommés : si le filtre contient un paramètre "_name", il est indexé par nom (le nom doit être unique)
     * et peut ensuite être manipulé en appellant hasFilter(), getFilter(), removeFilter().
     *
     * @param array $query Un tableau décrivant le filtre, en général créé avec le service QueryDSL. Exemple :
     *
     * <code>
     * $request->addFilter($dsl->term('status', 'publish')
     * </code>
     *
     * @return self
     */
    public function addGlobalFilter(array $filter)
    {
        $name = $this->getQueryName($filter);
        if (is_null($name)) {
            $this->globalFilters[] = $filter;
        } else {
            if (isset($this->globalFilters[$name])) {
                throw new InvalidArgumentException("A global filter named '$name' already exists");
            }
            $this->globalFilters[$name] = $filter;
        }

        return $this;

    }

    // -------------------------------------------------------------------------------
    // Tri
    // -------------------------------------------------------------------------------

    /**
     * Retourne le tri en cours.
     *
     * @return string|array|null Retourne le nom symbolique du tri en cours, un tableau contenant la définition du tri
     * en cours ou null si aucun tri n'a été défini.
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * Définit le tri en cours.
     *
     * Exemple :
     *
     * <code>
     * setSort([
     *     'lastupdate' => ['order' => 'asc'],
     *     'creation' => ['order' => 'asc', 'missing' => '_first'],
     * ]);
     * </code>
     *
     * @param array|string|null $sort Le tri peut être définit de plusieurs manières. Vous pouvez indiquer :
     * - une chaine contenant un nom de tri symbolique. Dans ce cas, lors de l'exécution de la requête, la définition
     *   du tri sera récupérée en appelant le filtre docalist_search_get_sort().
     * - un tableau contenant la définition du tri (dans ce cas, elle est utilisée telle quelle).
     * - null pour supprimer le tri en cours.
     *
     * @return self
     */
    public function setSort($sort)
    {
        $this->sort = $sort;

        return $this;
    }

    // -------------------------------------------------------------------------------
    // Liste des champs retournés
    // -------------------------------------------------------------------------------

    /**
     * Retourne le filtre utilisé par elasticsearch pour déterminer les champs qui seront retournés pour chaque hit.
     *
     * @return bool|string|array
     */
    public function getSourceFilter()
    {
        return $this->sourceFilter;
    }

    /**
     * Définit le filtre utilisé par elasticsearch pour déterminer les champs qui seront retournés pour chaque hit.
     *
     * cf. https://www.elastic.co/guide/en/elasticsearch/reference/master/search-request-source-filtering.html
     *
     * Remarque : le filtre ne concerne que les champs présents dans les documents indexés. Les champs spéciaux de
     * elasticsearch (_index, _type, _id, etc.) sont toujours retournés pour chaque hit.
     *
     * @param bool|string|array $filter Un filtre indiquant les champs à retourner :
     * - false : ne retourner aucun champ (valeur par défaut).
     * - true  : retourner tous les champs
     * - string : une liste de champ ou de masques (exemple : "creation,title*,event.*")
     * - array : une liste de champ ou de masques (exemple : ['creation', 'title*', 'event.*'])
     */
    public function setSourceFilter($filter)
    {
        if (!is_bool($filter) && !is_string($filter) && !is_array($filter)) {
            throw new InvalidArgumentException("Invalid source filter, expected bool, string or array");
        }

        if (is_array($filter)) {
            if (empty($filter)) {
                $filter = false;
            } elseif(count($filter) === 1) {
                $filter = reset($filter);
            } else {
                $filter = array_map('trim', $filter);
            }
        }

        if (is_string($filter)) {
            if ($filter === '') {
                $filter = false;
            } elseif ($filter === '*') {
                $filter = true;
            } else {
                $filter = array_map('trim', explode(',', $filter));
                if(count($filter) === 1) {
                    $filter = reset($filter);
                }
            }
        }

        $this->sourceFilter = $filter;

        return $this;
    }

    // -------------------------------------------------------------------------------
    // Agrégations
    // -------------------------------------------------------------------------------

    /**
     * Indique si la recherche contient des agrégations.
     *
     * @return bool
     */
    public function hasAggregations()
    {
        return !empty($this->aggregations);
    }

    /**
     * Retourne les agrégations qui composent la recherche.
     *
     * @return Aggregation[]|array[] Un tableau de la forme nom => agrégation.
     *
     * Chaque élément du tableau est un objet Aggregation ou un tableau.
     */
    public function getAggregations()
    {
        return $this->aggregations;
    }

    /**
     * Définit les agrégations qui composent la recherche.
     *
     * @param Aggregation[]|array[] $aggregations Un tableau d'agrégations de la forme nom => agrégation.
     *
     * Chaque élément du tableau est un objet Aggregation ou un tableau.
     *
     * Si la méthode est appelée sans arguments ou avec un tableau vide, la liste des agrégations est réinitialisée.
     *
     * @return self
     */
    public function setAggregations(array $aggregations = [])
    {
        $this->aggregations = [];
        foreach($aggregations as $name => $aggregation) {
            $this->addAggregation($name, $aggregation);
        }

        return $this;
    }

    /**
     * Ajoute une agrégation à la liste des agrégations qui composent la recherche.
     *
     * @param string $name Nom de l'agrégation.
     * @param Aggregation|array $aggregation Un objet Aggregation ou un tableau décrivant l'agrégation.
     *
     * @return self
     */
    public function addAggregation($name, $aggregation)
    {
        if (isset($this->aggregations[$name])) {
            throw new InvalidArgumentException("An aggregation named '$name' already exists");
        }

        if ($aggregation instanceof Aggregation) {
            $aggregation->setName($name)->setSearchRequest($this);
        } elseif (! is_array($aggregation)) {
            throw new InvalidArgumentException("Invalid aggregation '$name': expected array or Aggregation object");
        }

        $this->aggregations[$name] = $aggregation;

        return $this;
    }

    /**
     * Indique si la recherche contient l'agrégation indiquée.
     *
     * @param string $name Le nom de l'agrégation à tester.
     *
     * @return bool
     */
    public function hasAggregation($name)
    {
        return isset($this->aggregations[$name]);
    }

    /**
     * Retourne l'agrégation dont le nom est indiqué.
     *
     * @param string $name Le nom de l'agrégation à retourner.
     *
     * @return Agregation|array|null Retourne l'agrégation demandée (sous la forme d'un objet Aggregation ou
     * d'un tableau) ou null si aucune agrégation n'a le nom indiqué.
     */
    public function getAggregation($name)
    {
        return isset($this->aggregations[$name]) ? $this->aggregations[$name] : null;
    }

    /**
     * Supprime l'agrégation indiquée.
     *
     * Remarque : aucune erreur n'est générée si l'agrégation indiquée n'existe pas.
     *
     * @param string $name Le nom de l'agrégation à supprimer.
     *
     * @return self
     */
//     public function removeAggregation($name)
//     {
//         unset($this->aggregations[$name]);

//         return $this;
//     }

    // -------------------------------------------------------------------------------
    // Exécution
    // -------------------------------------------------------------------------------

    public function buildRequest() {
        $dsl = docalist('elasticsearch-query-dsl'); /** @var QueryDSL $dsl */

        $clauses = [];

        // Queries
        foreach($this->queries as $query) {
            $clauses[] = $dsl->must($query); // must ? should ?
        }

        // Filters
        foreach($this->filters as $filter) {
            $clauses[] = $dsl->filter($filter);
        }

        // Crée le filtre permettant de limiter la recherche aux types de contenus indiqués : type1 OR type2...
        $indexManager = docalist('docalist-search-index-manager'); /** @var IndexManager $indexManager */
        if (count($this->types) === 1) {
            $type = reset($this->types);
            $filter = $indexManager->getIndexer($type)->getSearchFilter();
        } else {
            $filters = [];
            foreach($this->types as $type) {
                $filter = $indexManager->getIndexer($type)->getSearchFilter();
                $filters[] = $dsl->should($filter);
            }
            $filter = $dsl->bool($filters);
        }
        $clauses[] = $dsl->filter($filter);

        // Global Filters
        foreach($this->globalFilters as $filter) {
            $clauses[] = $dsl->filter($filter);
        }

        // Combine les différentes clauses
        $request = [];
        if (empty($clauses)) {
            // $request['query'] = $dsl->matchAll(); // inutile, c'est ce que fait ES Par défaut
        } elseif(count($clauses) === 1) {
            $clauses = reset($clauses); // On obtient une clause de la forme "must" => []
            $request['query'] = reset($clauses); // On obtient la query qui figure dans la clause
        } else {
            $request['query'] = $dsl->bool($clauses);
        }

        // post filters
        if ($this->postFilters) {
            $clauses = [];
            foreach($this->postFilters as $filter) {
                $clauses[] = $dsl->filter($filter);
            }

            if(count($clauses) === 1) {
                $clauses = reset($clauses); // On obtient une clause de la forme "must" => []
                $request['post_filter'] = reset($clauses); // On obtient la query qui figure dans la clause
            } else {
                $request['post_filter'] = $dsl->bool($clauses);
            }
        }

        // Numéro du premier hit
        $this->page > self::DEFAULT_PAGE && $request['from'] = ($this->page - 1) * $this->size;

        // Nombre de réponses par page
        $this->size !== self::DEFAULT_SIZE && $request['size'] = $this->size;

        // Champs _source à retourner
        $request['_source'] = $this->sourceFilter;

        // Expliquer les hits obtenus
        // $this->explainHits && $request['explain'] = true;

        // Agrégrations
        if ($this->aggregations) {
            $request['aggregations'] = [];
            foreach($this->aggregations as $name => $aggregation) {
                ($aggregation instanceof Aggregation) && $aggregation = $aggregation->getDefinition();
                $request['aggregations'][$name] = $aggregation;
            }
        }

        // Tri (gèré en dernier pour passer un objet SearchRequest quasi complet aux filtres)
        if ($this->size) { // tri inutile si size===0
            // Si on n'a aucun critère de tri, on utilise le tri par défaut retourné par le filtre
            if (is_null($this->sort)) {
                $this->sort = apply_filters('docalist_search_get_default_sort', null, $this);
            }

            // Si le tri en cours est un tableau (des clauses de tri elasticsearch), on prend tel quel
            if (is_array($this->sort)) {
                $request['sort'] = $this->sort;
            }

            // Sinon (chaine), il faut traduire le nom de tri en clauses de tri elasticsearch.
            else {
                $sort = apply_filters('docalist_search_get_sort', null, $this->sort);
                $sort ? ($request['sort'] = $sort) : ($this->sort = 'score');
            }
        }

        // Ok
        return $request;
    }

    /**
     * Envoie la requête au serveur elasticsearch passé en paramètre et retourne les résultats obtenus.
     *
     * @param array $options Options de la recherche. Les valeurs possibles sont les suivantes :
     *
     * - search_type : mode d'exécution de la requête sur les différents shards du cluster elasticsearch.
     *   ('query_then_fetch' ou 'dfs_query_then_fetch').
     *   cf. https://www.elastic.co/guide/en/elasticsearch/reference/master/search-request-search-type.html
     *
     * - filter_path : filtres sur les informations à retourner dans la réponse.
     *   cf. https://www.elastic.co/guide/en/elasticsearch/reference/master/common-options.html#_response_filtering
     *
     * @return SearchResponse|null Un objet SearchResponse décrivant les résultats de la recherche ou null si
     * elasticsearch a généré une erreur.
     */
    public function execute(array $options = [])
    {
        // Construit les paramètres de la recherche à partir des options indiquées
        $queryString = '';

        // https://www.elastic.co/guide/en/elasticsearch/reference/master/search-request-search-type.html
        if (isset($options['search_type'])) {
            $option = $options['search_type'];
            if (!in_array($option, ['query_then_fetch', 'dfs_query_then_fetch'])) {
                throw new InvalidArgumentException("Invalid search type, expected query_then_fetch or dfs_query_then_fetch");
            }
            $queryString .= "&search_type=$option";
        }

        // https://www.elastic.co/guide/en/elasticsearch/reference/master/common-options.html#_response_filtering
        if (isset($options['filter_path'])) {
            $option = $options['filter_path'];
            $queryString .= '&filter_path=' . urlencode($option);
        }

        // scroll : https://www.elastic.co/guide/en/elasticsearch/reference/master/search-request-scroll.html
        // preference : https://www.elastic.co/guide/en/elasticsearch/reference/master/search-request-preference.html

        // explain : pas en querystring https://www.elastic.co/guide/en/elasticsearch/reference/master/search-request-explain.html
        // version : pas en querystring https://www.elastic.co/guide/en/elasticsearch/reference/master/search-request-version.html

        // Finalise la querystring
        $queryString && $queryString[0] = '?';

        // Exécute la requête
        $es = docalist('elastic-search');
        $data = $es->get("/{index}/_search$queryString", $this->buildRequest());
        if (isset($data->error)) {
            $this->hasErrors = true;

            return null;
        }

        $this->hasErrors = false;

        // Crée l'objet SearchResponse (sans données pour le moment)
        $searchResponse = new SearchResponse($this);

        // Fournit le résultat obtenu à chaque agrégation et remplace le résultat brut par l'objet Aggregation
        foreach($this->aggregations as $name => $aggregation) {
            if ($aggregation instanceof Aggregation) {
                $result = isset($data->aggregations->$name) ? $data->aggregations->$name : null;
                $data->aggregations->$name = $aggregation->setSearchResponse($searchResponse)->setResult($result);
            }
        }

        // Fournit les données finales à l'objet SearchResponse
        $searchResponse->setData($data);

        // Retourne les résultats
        return $searchResponse;
    }

    /**
     * Indique si la dernière exécution de la requête a généré des erreurs.
     *
     * N'a de sens que si execute() a déjà été appelé.
     *
     * @return bool
     */
    public function hasErrors()
    {
        return $this->hasErrors;
    }

    // -------------------------------------------------------------------------------
    // Méthodes internes
    // -------------------------------------------------------------------------------

    /**
     * Retourne un paramètre d'une requête écrite en QueryDSL.
     *
     * Exemple : getQueryParameter('_name')
     *
     * @param array  $query Requête
     * @param string $name  Nom du paramètre.
     *
     * @return scalar|null
     */
    protected function getQueryParameter(array $query, $name)
    {
        /* Pour certaines query, le paramètre "_name" figure au 1er niveau, pour d'autres au niveau suivant :
         * - {"match_all": {"_name": "matchall"}}
         * - {"term": {"title": {"value": "hello", "_name": "term"}}}
         * (cf. https://github.com/elastic/elasticsearch/issues/11744)
         *
         * 'bool' => 1, 'exists' => 1, 'ids' => 1, 'match' => 2, 'match_all' => 1, 'multi_match' => 1,
         * 'prefix' => 2, 'query_string' => 1, 'range' => 2, 'simple_query_string' => 1,
         * 'term' => 2, 'terms' => 1, 'type' => 1, 'wildcard' => 2,
         */

        // Vérifie que $query est bien une requête et récupère sont type et l'élément de premier niveau
        if (empty($query) || !is_array($item = reset($query))) {
            throw new InvalidArgumentException('Invalid query, expected array of arrays');
        }

        // Teste au niveau 1
        if (isset($item[$name]) && is_scalar($item[$name])) {
            return $item[$name];
        }

        // Teste au niveau 2
        $item = reset($item);
        return is_array($item) && isset($item[$name]) && is_scalar($item[$name]) ? $item[$name] : null;
    }

    /**
     * Retourne le nom de la requête passée en paramètre (paramètre "_name").
     *
     * @param array $query
     *
     * @return scalar|null
     */
    protected function getQueryName(array $query) {
        return $this->getQueryParameter($query, '_name');
    }
}
