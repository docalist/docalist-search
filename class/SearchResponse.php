<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2013-2016 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search;

use stdClass;
use Docalist\Search\SearchRequest2 as SearchRequest;

/**
 * Le résultat d'une requête de recherche adressée à ElasticSearch.
 */
class SearchResponse
{
    /**
     * La requête qui a généré les résultats.
     *
     * @var SearchRequest
     */
    protected $request;

    /**
     * La réponse brute retournée par ElasticSearch.
     *
     * @var stdClass
     */
    protected $data;

    /**
     * Initialise l'objet à partir de la réponse retournée par Elastic Search.
     *
     * @param SearchRequest $request    L'objet SearchRequest qui a généré ces résultats.
     * @param stdClass      $data       La réponse brute retournée par ElasticSearch.
     */
    public function __construct(SearchRequest $request, stdClass $data)
    {
        $this->request = $request;
        $this->data = $data;
    }

    /**
     * Retourne la requête qui a généré cet objet résultat.
     *
     * @return SearchRequest
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Retourne les données brutes retournées par elasticsearch.
     *
     * @return stdClass
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Indique si la requête a généré un time out.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/search-request-body.html#_parameters_4
     *
     * @return bool
     */
    public function isTimedOut()
    {
        return isset($this->data->timed_out) ? $this->data->timed_out : false;
    }

    /**
     * Indique si la recherche a été arrêtée avant d'avoir collecté toutes les réponses.
     *
     * Ce flag n'existe que si la requête exécutée contenant un paramètre "terminate_after".
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/search-request-body.html#_parameters_4
     *
     * @return bool
     */
    public function isTerminatedEarly()
    {
        return isset($this->data->terminated_early) ? $this->data->terminated_early : false;
    }

    /**
     * Retourne le temps (en millisecondes) mis par ElasticSearch pour exécuter la requête.
     *
     * Ce temps correspond au temps d'exécution de la requête sur les différents shards.
     *
     * Il ne comprend le temps passé au cours des étape suivantes :
     *
     * - sérialisation de la requête (nous)
     * - envoi de la requête à Elastic Search (réseau)
     * - désérialisation de la requête (par ES)
     *
     * - sérialisation de la réponse (ES)
     * - transit de la réponse (réseau)
     * - désérialisation de la réponse
     *
     * @return int durée en millisecondes
     *
     * @link @see http://elasticsearch-users.115913.n3.nabble.com/query-timing-took-value-and-what-I-m-measuring-tp4026185p4026226.html
     */
    public function getTook()
    {
        return isset($this->data->took) ? $this->data->took : 0;
    }

    /**
     * Retourne le nombre total de shards qui ont exécuté la requête.
     *
     * @return int
     */
    public function getTotalShards()
    {
        return isset($this->data->_shards->total) ? $this->data->_shards->total : 0;
    }

    /**
     * Retourne le nombre de shards qui ont réussi à exécuter la requête.
     *
     * @return int
     */
    public function getSuccessfulShards()
    {
        return isset($this->data->_shards->successful) ? $this->data->_shards->successful : 0;
    }

    /**
     * Retourne le nombre de shards qui ont échoué à exécuter la requête.
     *
     * @return int
     */
    public function getFailedShards()
    {
        return isset($this->data->_shards->failed) ? $this->data->_shards->failed : 0;
    }

    /**
     * Retourne le nombre total de documents qui répondent à la requête exécutée.
     *
     * @return int
     */
    public function getHitsCount()
    {
        return isset($this->data->hits->total) ? $this->data->hits->total : 0;
    }

    /**
     * Retourne le score maximal obtenu par la meilleure réponse.
     *
     * @return float
     */
    public function getMaxScore()
    {
        return isset($this->data->hits->max_score) ? $this->data->hits->max_score : 0.0;
    }

    /**
     * Retourne la liste des réponses obtenues.
     *
     * @return array Chaque réponse est un objet contenant les propriétés suivantes :
     *
     * _id : numéro de référence de l'enregistrement
     * _score : score obtenu
     * _index : nom de l'index ElasticSearch d'où provient le hit
     * _type : type du hit
     */
    public function getHits()
    {
        return isset($this->data->hits->hits) ? $this->data->hits->hits : [];
    }

    /**
     * Retourne les agrégations obtenues.
     *
     * @return array
     */
    public function getAggregations()
    {
        return isset($this->data->aggregations) ? (array) $this->data->aggregations : [];
    }

    /**
     * Indique si l'agrégation indiquée existe.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasAggregation($name)
    {
        return isset($this->data->aggregations->$name);
    }

    /**
     * Retourne une agrégation.
     *
     * @param string $name
     *
     * @return Aggregation|array|null
     */
    public function getAggregation($name)
    {
        return isset($this->data->aggregations->$name) ? $this->data->aggregations->$name : null;
    }
}
