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
 */
namespace Docalist\Search;

use Docalist\Search\QueryDSL;
use InvalidArgumentException;

/**
 * Une requête de recherche adressée à ElasticSearch.
 */
class SearchRequest2
{
    /**
     * Numéro de la page de résultats à retourner (1-based).
     *
     * @var int
     */
    protected $page = 1;

    /**
     * Nombre de réponses par page.
     *
     * @var int
     */
    protected $size = 10;

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
     * Filtre global appliqué à la recherche.
     *
     * @var array|null
     */
    protected $globalFilter = null;

    /**
     * Liste des clauses de tri.
     *
     * @var array
     */
    protected $sort = [];

    /**
     * Contrôle la liste des champs qui seront retournés pour chaque hit.
     *
     * @var bool|string|array
     */
    protected $sourceFilter = false;

    /**
     * Indique si la requête exécutée a des erreurs.
     *
     * Initialisé lorsque execute() est appelée.
     *
     * @var bool
     */
    protected $hasErrors = false;


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
     * Les aggrégations éventuelles et les autres paramètres de la recherche (size, page...) ne sont pas
     * pris en compte.
     *
     * @return bool
     */
    public function isEmptyRequest()
    {
        return !$this->hasQueries() && !$this->hasFilters() && !$this->hasGlobalFilter();
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
     * @return int Un entier >= 1
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
    public function hasQuery($query)
    {
        if (is_string($query)) {
            return isset($this->queries[$query]);
        }

        return in_array($query, $this->queries, true);
    }

    /**
     * Retourne la requête nommée dont le nom est indiqué.
     *
     * @param string $name Le nom de la requête à retourner.
     *
     * @return array|null Retourne la requête demandée ou null si aucune requête n'a le nom indiqué.
     */
    public function getQuery($name)
    {
        return isset($this->queries[$name]) ? $this->queries[$name] : null;
    }

    /**
     * Supprime la requête passée en paramétre.
     *
     * Remarque : aucune erreur n'est générée si la requête indiquée n'existe pas.
     *
     * @param string|array $query Le nom de la requête nommée à supprimer ou un tableau décrivant la requête.
     *
     * @return self
     */
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

    // -------------------------------------------------------------------------------
    // Filtres utilisateurs
    // -------------------------------------------------------------------------------

    /**
     * Indique si la recherche contient des fitlres utilisateurs.
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
     * Indique si la recherche contient le filtre utilisateur indiqué.
     *
     * @param string|array $filter Le nom du filtre nommé à tester ou un tableau décrivant le filtre.
     *
     * @return bool
     */
    public function hasFilter($filter)
    {
        if (is_string($filter)) {
            return isset($this->filters[$filter]);
        }

        return in_array($filter, $this->filters, true);

    }

    /**
     * Retourne le filtre nommé dont le nom est indiqué.
     *
     * @param string $name Le nom du filtre à retourner.
     *
     * @return array|null Retourne le filtre demandé ou null si aucun filtre n'a le nom indiqué.
     */
    public function getFilter($name)
    {
        return isset($this->filters[$name]) ? $this->filters[$name] : null;
    }

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
    public function toggleFilter(array $filter)
    {
        return $this->hasFilter($filter) ? $this->removeFilter($filter) : $this->addFilter($filter);
    }

    // -------------------------------------------------------------------------------
    // Filtre global (caché)
    // -------------------------------------------------------------------------------

    /**
     * Indique si la recherche contient des fitlres utilisateurs.
     *
     * @return bool
     */
    public function hasGlobalFilter()
    {
        return !is_null($this->globalFilter);
    }

    /**
     * Retourne le filtre global appliqué à la recherche.
     *
     * @return array|null
     */
    public function getGlobalFilter()
    {
        return $this->globalFilter;
    }

    /**
     * Définit le filtre global appliqué à la recherche.
     *
     * @param array|null $filter Le filtre global à appliquer.
     *
     * Si la méthode est appelée sans paramétre, avec null ou avec un tableau vide, le filtre global est réinitialisé.
     *
     * @return self
     */
    public function setGlobalFilter(array $filter = null)
    {
        $this->globalFilter = $filter ? $filter : null;

        return $this;
    }

    // -------------------------------------------------------------------------------
    // Tri
    // -------------------------------------------------------------------------------

    /**
     * Retourne la liste des clauses de tri ou une clause de tri particulière.
     *
     * @param string $field Optionnel, nom de champ. Si vous n'indiquez aucun champ, la méthode retourne toutes les
     * clauses de tri. Si vous indiquez un champ, elle retourne la clause de tri pour ce champ (ou null si le champ
     * n'est pas utilisé comme clé de tri).
     *
     * @return array Un tableau (éventuellement vide) contenant les différentes clauses de tri dans l'ordre où elles
     * ont été ajoutées par addSort(). Exemple :
     *
     * <code>
     * [
     *     'lastupdate' => ['order' => 'asc'],
     *     'creation' => ['order' => 'asc', 'missing' => '_first'],
     * ]
     * </code>
     *
     * Si vous avez indiqué un nom de champ, elle retourne un tableau contenant les options du champ. Exemple :
     *
     * <code>
     * getSort('creation'); // ['order' => 'asc', 'missing' => '_first']
     * </code>
     */
    public function getSort($field = null)
    {
        return is_null($field) ? $this->sort : (isset($this->sort[$field]) ? $this->sort[$field] : null);
    }

    /**
     * Définit la liste des clauses de tri.
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
     * @param array $sortClauses Un tableau contenant les différentes clauses de tri. Chaque clause du tableau est
     * ajoutée en appellant addSort(). Si le tableau est vide, les clauses de tri sont réinitialisées (i.e. tri
     * elasticsearch par défaut : _score desc).
     *
     * @return self
     */
    public function setSort(array $sortClauses)
    {
        $this->sort = [];
        foreach($sortClauses as $field => $options) {
            $order = '';
            if (isset($options['order'])) {
                $order = $options['order'];
                unset($options['order']);
            }
            $this->addSort($field, $order, $options);
        }

        return $this;
    }

    /**
     * Ajoute une clé de tri.
     *
     * cf. https://www.elastic.co/guide/en/elasticsearch/reference/master/search-request-sort.html
     *
     * Exemples : :
     *
     * <code>
     * addSort('lastupdate');                           // tri par date de mise à jour croissante
     * addSort(['lastupdate' => 'asc']);                // idem
     * addSort(['lastupdate' => ['order' => 'asc']])    // idem
     *
     * addSort('_score');                               // tri par pertinence décroissante
     *
     * </code>
     *
     * @param string|array $clause Une clause de tri qui sera ajoutée à la liste des clauses existantes.
     */
    public function addSort($field, $order = '', array $options = [])
    {
        // Vérifie le champ
        if (! is_string($field)) {
            throw new InvalidArgumentException('Invalid sort field, expected string');
        }

        // Vérifie l'ordre de tri
        if (! is_string($order)) {
            throw new InvalidArgumentException("Invalid sort order for '$field', expected string");
        }
        empty($order) && $order = ($field === '_score') ? 'desc' : 'asc';
        if ($order !== 'asc' && $order !== 'desc') {
            throw new InvalidArgumentException("Invalid sort order for '$field', expected 'asc' or 'desc'");
        }

        // Liste des options autorisées
        // cf. code source de elasticsearch pour voir les options disponibles pour chaque type de tri :
        // - tris dispos :  src/main/java/org/elasticsearch/search/sort/SortBuilder.java::Map()
        // - script sort :  src/main/java/org/elasticsearch/search/sort/ScriptSortBuilder.java::fromXContent()
        // - geo-distance : src/main/java/org/elasticsearch/search/sort/GeoDistanceSortBuilder.java::fromXContent()
        // - score sort :   src/main/java/org/elasticsearch/search/sort/ScoreSortBuilder.java::fromXContent()
        // - field sort :   src/main/java/org/elasticsearch/search/sort/FieldSortBuilder.java::fromXContent()
        $accept = [
            'nested_filter',
            'nested_path',
            'missing',  // valeurs autorisées : _last, _first ou une valeur
         // 'reverse',  // deprecated / non documenté
         // 'order',    // fixé par nous (paramètre), non autorisé comme option
            'mode',     // valeurs autorisées : min, max, sum, avg ou median
            'unmapped_type',
         // 'type', // ?
            'script'
        ];

        // Vérifie les options indiquées
        if ($options && $bad = array_diff(array_keys($options), $accept)) {
            throw new InvalidArgumentException("Invalid sort options for field '$field': " . implode(', ', $bad));
        }

        // Stocke la clause de tri
        if (isset($this->sort[$field])) {
            unset($this->sort[$field]);
        }
        $this->sort[$field] = ['order' => $order] + $options;

        // Ok
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
    // Exécution
    // -------------------------------------------------------------------------------

    protected function buildRequest() {
        $dsl = docalist('elasticsearch-query-dsl'); /* @var QueryDSL $dsl */

        $request = [];
        $clauses = [];

        // Queries
        foreach($this->queries as $query) {
            $clauses[] = $dsl->must($query); // must ? should ?
        }

        // Filters
        foreach($this->filters as $filter) {
            $clauses[] = $dsl->filter($filter);
        }

        // Global Filter
        if ($this->globalFilter) {
            $clauses[] = $dsl->filter($this->globalFilter);
        }

        // Combine les différentes clauses
        $request['query'] = $dsl->bool($clauses);

        // Tri
        if ($this->sort) {
            $request['sort'] = $this->sort;
        }

        // Numéro du premier hit
        $this->page > 1 && $request['from'] = ($this->page - 1) * $this->size;

        // Nombre de réponses par page
        $this->size !== 10 && $request['size'] = $this->size;

        // Expliquer les hits obtenus
        // $this->explainHits && $request['explain'] = true;


        return $request;
    }

    /**
     * Envoie la requête au serveur elasticsearch passé en paramètre et retourne les résultats obtenus.
     *
     * @param string Mode d'exécution de la requête sur les différents shards du cluster elasticsearch.
     *
     * cf. https://www.elastic.co/guide/en/elasticsearch/reference/master/search-request-search-type.html
     *
     * @return SearchResults|null Un objet SearchResults décrivant les résultats de la recherche ou null
     * si elasticsearch a généré une erreur.
     */
    public function execute($searchType = 'query_then_fetch')
    {
        if (!in_array($searchType, ['query_then_fetch', 'dfs_query_then_fetch'])) {
            throw new InvalidArgumentException("Invalid search type, expected query_then_fetch or dfs_query_then_fetch");
        }

        $es = docalist('elastic-search');
        $response = $es->get("/{index}/_search?search_type=$searchType", $this->buildRequest());
        if (isset($response->error)) {
            $this->hasErrors = true;

            return null;
        }

        $this->hasErrors = false;

        return new SearchResults($response, $es->getElapsedTime());
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