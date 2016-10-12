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
namespace Docalist\Search\Aggregation;

use Docalist\Search\Aggregation;
use Docalist\Search\SearchRequest2 as SearchRequest;
use Docalist\Search\SearchResponse;
use stdClass;

/**
 * Classe de base pour les agrégations.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations.html
 */
abstract class BaseAggregation implements Aggregation
{
    /**
     * Type d'agrégation.
     *
     * @var string
     */
    const TYPE = null;

    /**
     * Vue par défaut pour render() et display().
     *
     * @var string
     */
    const DEFAULT_VIEW = 'docalist-search:aggregations/base';

    /**
     * Nom de l'agrégation.
     *
     * @var string
     */
    protected $name;

    /**
     * Titre de l'agrégation.
     *
     * @var string
     */
    protected $title;

    /**
     * Paramètres de l'agrégation.
     *
     * @var array
     */
    protected $parameters;

    /**
     * Résultat de l'agrégation (objet contenant les données retournées par elasticsearch).
     *
     * @var object
     */
    protected $result;

    /**
     * L'objet SearchRequest qui a créé cette agrégation.
     *
     * @var SearchRequest
     */
    protected $searchRequest;

    /**
     * L'objet SearchResponse qui a généré les résultats de cette agrégation.
     *
     * @var SearchResponse
     */
    protected $searchResponse;

    /**
     * Constructeur : initialise l'agrégation avec les paramètres indiqués.
     *
     * @param array $parameters
     */
    public function __construct($parameters = [])
    {
        $this->setParameters($parameters);
    }

    public function getType()
    {
        return static::TYPE;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name ?: get_class($this);
    }

    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function setParameter($name, $value)
    {
        if (is_null($value)) {
            unset($this->parameters[$name]);
        } else {
            $this->parameters[$name] = $value;
        }

        return $this;
    }

    public function getParameter($name)
    {
        return isset($this->parameters[$name]) ? $this->parameters[$name] : null;
    }

    public function hasParameter($name)
    {
        return isset($this->parameters[$name]);
    }

    public function getDefinition()
    {
        return [$this->getType() => $this->getParameters() ?: (object) []];
    }

    public function setResult(stdClass $result)
    {
        $this->result = $result;

        return $this;
    }

    public function getResult($name = null)
    {
        return is_null($name) ? $this->result : (isset($this->result->$name) ? $this->result->$name : null);
    }

    public function setSearchRequest(SearchRequest $searchRequest)
    {
        $this->searchRequest = $searchRequest;

        return $this;
    }

    public function getSearchRequest()
    {
        return $this->searchRequest;
    }

    public function setSearchResponse(SearchResponse $searchResponse)
    {
        $this->searchResponse = $searchResponse;

        return $this;
    }

    public function getSearchResponse()
    {
        return $this->searchResponse;
    }

    public function getDefaultView()
    {
        return static::DEFAULT_VIEW;
    }

    public function display($view = null, array $data = [])
    {
        is_array($view) && $data = $view;
        !is_string($view) && $view = $this->getDefaultView();

        return docalist('views')->display($view, ['this' => $this] + $data);
    }

    public function render($view = null, array $data = [])
    {
        ob_start();
        $this->display($view, $data);

        return ob_get_clean();
    }
}
