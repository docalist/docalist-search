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

use InvalidArgumentException;
use Docalist\Search\SearchRequest\QueryTrait;
use Docalist\Search\SearchRequest\PageTrait;
use Docalist\Search\SearchRequest\SizeTrait;
use Docalist\Search\SearchRequest\SortTrait;
use Docalist\Search\SearchRequest\SourceTrait;
use Docalist\Search\SearchRequest\SearchUrlTrait;
use Docalist\Search\SearchRequest\AggregationsTrait;
use Docalist\Search\SearchRequest\EquationTrait;

/**
 * Une requête de recherche adressée à ElasticSearch.
 */
class SearchRequest
{
    use QueryTrait, PageTrait, SizeTrait, SortTrait, SourceTrait, SearchUrlTrait, AggregationsTrait, EquationTrait;

    const DEFAULT_SIZE = 10; // Compatibilité ascendante pour thème SVB

    /**
     * Indique si la requête exécutée a des erreurs.
     *
     * Initialisé lorsque execute() est appelée.
     *
     * @var bool
     */
    protected $hasErrors = false;

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

    /**
     * Indique si la requête de recherche est vide
     *
     * La requête est considérée comme vide si elle ne contient aucune requête et aucun filtre (les agrégations
     * éventuelles et les paramètres de la recherche comme size ou page ne sont pas pris en compte).
     *
     * @return bool
     */
    public function isEmptyRequest()
    {
        return !$this->hasQueries() && !$this->hasFilters();
    }

    // -------------------------------------------------------------------------------
    // Exécution
    // -------------------------------------------------------------------------------
    protected function buildRequest()
    {
        $request = [];

        // Requête à exécuter
        $this->buildQueryClause($request);

        // post filters
        $this->buildPostFilterClause($request);

        // Numéro du premier hit
        $this->buildPageClause($request);

        // Nombre de réponses par page
        $this->buildSizeClause($request);

        // Tri
        $this->buildSortClause($request);

        // Champs _source à retourner
        $this->buildSourceClause($request);

        // Expliquer les hits obtenus
        // $this->explainHits && $request['explain'] = true;

        // Agrégrations
        $this->buildAggregationsClause($request);

        // Excerpts et mise en surbrillance des mots trouvés
/* // test
        $request['highlight'] = [
            //'encoder' => 'html',
            'type' => 'plain',
            'number_of_fragments' => 10,
            'fragment_size' => 80,
            'tags_schema' => 'styled',
            'fragmenter' => 'span',
            'fields' => [
                ['titre' => new \stdClass()],
                ['poste' => new \stdClass()],
                ['profil' => new \stdClass()],
            ]
        ];
*/
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
        // Construit la requête
        $request = $this->buildRequest();

        // Construit les paramètres de la recherche à partir des options indiquées
        $queryString = $this->getExecuteOptions($options);

        // Exécute la requête
        $data = docalist('elastic-search')->get("/{index}/_search$queryString", $request);
        if (isset($data->error)) {
            $this->hasErrors = true;

            return null;
        }

        // Crée l'objet SearchResponse (sans données pour le moment)
        $this->hasErrors = false;
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
     * Retourne la query-string à ajouter à l'url /_search en fonction des options passées en paramètre.
     *
     * @param array $options
     *
     * @return string
     */
    protected function getExecuteOptions(array $options)
    {
        // Construit les paramètres de la recherche à partir des options indiquées
        $queryString = '';

        // https://www.elastic.co/guide/en/elasticsearch/reference/master/search-request-search-type.html
        if (isset($options['search_type'])) {
            $option = $options['search_type'];
            unset($options['search_type']);
            $allowed = ['query_then_fetch', 'dfs_query_then_fetch'];
            if (!in_array($option, $allowed)) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid search_type "%s", expected "%s"',
                    $option,
                    implode('" or "', $allowed)
                ));
            }
            $queryString .= '&search_type=' . urlencode($option);
        }

        // https://www.elastic.co/guide/en/elasticsearch/reference/master/common-options.html#_response_filtering
        if (isset($options['filter_path'])) {
            $option = $options['filter_path'];
            unset($options['filter_path']);
            $queryString .= '&filter_path=' . urlencode($option);
        }

        // scroll : https://www.elastic.co/guide/en/elasticsearch/reference/master/search-request-scroll.html
        // preference : https://www.elastic.co/guide/en/elasticsearch/reference/master/search-request-preference.html

        // explain : pas en querystring https://www.elastic.co/guide/en/elasticsearch/reference/master/search-request-explain.html
        // version : pas en querystring https://www.elastic.co/guide/en/elasticsearch/reference/master/search-request-version.html

        // Génère une exception s'il reste des options qu'on ne gère pas
        if (!empty($options)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported search options "%s"',
                implode('" and "', array_keys($options))
            ));
        }

        // Finalise la querystring
        $queryString && $queryString[0] = '?';

        // Ok
        return $queryString;
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
}
