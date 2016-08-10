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
use LogicException;

/**
 * Classe de base pour les agrégations.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations.html
 */
abstract class BaseAggregation implements Aggregation
{
    /**
     * Type d'aggrégation.
     *
     * @var string
     */
    const TYPE = null;

    /**
     * Vue par défaut pour render() et display()
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
     * Paramètres de l'agrégation.
     *
     * @var array
     */
    protected $parameters;

    /**
     * Résultats de l'agrégation.
     *
     * @var array
     */
    protected $results;

    /**
     * Nom de la vue à utiliser pour afficher cette agrégation.
     *
     * @var string
     */
    protected $view;

    /**
     * Données à transmettre à la vue lors de l'affichage..
     *
     * @var array
     */
    protected $viewData;

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
        // Sanity check : vérifie que la constante TYPE a été surchargée dans les classes descendantes concrêtes.
        if (empty(static::TYPE)) {
            throw new LogicException(get_class($this) . '::TYPE is not defined');
        }

        return static::TYPE;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
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
        return [$this->getType() => $this->getParameters()];
    }

    public function setResults($results)
    {
        $this->results = $results;

        return $this;
    }

    public function getResults()
    {
        return $this->results;
    }

    /**
     * Retourne le résultat dont le nom est indiqué ou null s'il ne figure pas dans les résultats de l'agrégation.
     *
     * @param string $name Nom du champ à retourner.
     *
     * @return mixed|null
     */
    protected function getResult($name)
    {
        return isset($this->results->$name) ? $this->results->$name : null;
    }

    public function setView($view)
    {
        $this->view = $view;

        return $this;
    }

    public function getView()
    {
        return $this->view ?: static::DEFAULT_VIEW;
    }

    public function setViewData(array $data)
    {
        $this->viewData = $data;

        return $this;
    }

    public function getViewData()
    {
        return $this->viewData;
    }

    public function display()
    {
        return docalist('views')->display($this->getView(), ['this' => $this] + $this->getViewData());
    }

    public function render()
    {
        return docalist('views')->render($this->getView(), ['this' => $this] + $this->getViewData());
    }
}
