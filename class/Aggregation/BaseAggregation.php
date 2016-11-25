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
     * Options d'affichage.
     *
     * @var array
     */
    protected $options;

    /**
     * Constructeur : initialise l'agrégation avec les paramètres indiqués.
     *
     * @param array $parameters     Paramètres de l'agrégation.
     * @param array $options        Options d'affichage.
     */
    public function __construct(array $parameters = [], array $options = [])
    {
        $this->parameters = $parameters;
        $this->options = $this->getDefaultOptions();
        $options && $this->setOptions($options);
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

            return $this;
        }

        $this->parameters[$name] = $value;

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

    /**
     * {@inheritDoc}
     *
     * Les options disponibles et leurs valeurs par défaut sont les suivantes :
     *
     * - 'container'     => true,    // Génère ou non un container.
     * - 'container.tag' => 'div',   // Tag à utiliser pour le container (si container est à true).
     * - 'container.css' => '',      // Classes css du tag container (en plus de celles qui sont générées).
     *
     * - 'title'         => $title,  // Titre de l'agrégation ou false pour ne pas afficher de titre.
     * - 'title.tag'     => 'h3',    // Tag à utiliser pour le titre (si title n'est pas à false).
     * - 'title.css'     => '',      // Classes css du tag titre
     * - 'title.before'  => true,    // Position du titre : true = avant le contenu, false = après.
     *
     * - 'content.tag'   => 'pre',   // Tag à utiliser pour le contenu de l'agrégation.
     * - 'content.css'    =>'',      // Classes css du tag contenu.
     *
     * - 'data'          => false,   // Génère ou non des attributs "data-xxx".
     *
     * SimpleMetric :
     * - 'zero'          => true,    // Affiche ou non l'agrégation si la valeur calculée est à zéro.
     *
     * @return array
     */
    public function getDefaultOptions()
    {
        $title = $this->getType() . ' ' . $this->getParameter('field');
        return [
            'container'         => true,    // Génère ou non un container.
            'container.tag'     => 'div',   // Tag à utiliser pour le container (si container est à true).
            'container.css'     => '',      // Classes css du tag container (en plus de celles qui sont générées).
            'container.tooltip' => '',      // Attribut title du tag container

            'title'             => $title,  // Titre de l'agrégation ou false pour ne pas afficher de titre.
            'title.tag'         => 'h3',    // Tag à utiliser pour le titre (si title n'est pas à false).
            'title.css'         => '',      // Classes css du tag titre
            'title.before'      => true,    // Position du titre : true = avant le contenu, false = après.

            'content.tag'       => 'pre',   // Tag à utiliser pour le contenu de l'agrégation.
            'content.css'       =>'',      // Classes css du tag contenu.

            'data'              => false,   // Génère ou non des attributs "data-xxx".
        ];
    }

    public function setOptions(array $options = [])
    {
        $this->options = $options + $this->options;

        return $this;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setOption($option, $value)
    {
        $this->options[$option] = $value;

        return $this;
    }

    public function getOption($option)
    {
        return isset($this->options[$option]) ? $this->options[$option] : null;
    }

    // ----------------------------------------------------------------------------------------------------
    // Affichage
    // ----------------------------------------------------------------------------------------------------

    public function display(array $options = null)
    {
        echo $this->render($options);

        return $this;
    }

    public function render(array $options = [])
    {
        // Tient compte des options d'affichage passées en paramètre
        $this->setOptions($options);

        // Génère le résultat, terminé si l'agrégation n'a rien généré
        if ('' === $content = $this->renderContent()) {
            return '';
        }

        // Génère le titre (avant ou après le résultat selon l'option 'title.before')
        if ('' !== $title = $this->renderTitle()) {
            $content = $this->options['title.before'] ? ($title . $content) : ($content . $title);
        }

        // Génère le containeur
        return $this->renderContainer($content);
    }

    // ----------------------------------------------------------------------------------------------------
    // API interne : méthodes destinées à être surchargées par les classes descendantes.
    // ----------------------------------------------------------------------------------------------------

    /**
     * Génère le container avec le contenu indiqué.
     *
     * Si l'option 'container' est à false, aucun container n'est généré et la méthode retourne simplement
     * le contenu passé en paramètre.
     *
     * Sinon, la méthode génère un bloc englobant le contenu fourni en utilisant le tag indiqué dans
     * l'option 'container.tag' et les attributs retournés par getContainerAttributes().
     *
     * @param string $content Contenu à insérer dans le container.
     *
     * @return string
     */
    protected function renderContainer($content)
    {
        // Si l'option 'container' est à false, on ne génère pas de container, on retourne juste le contenu
        if ($this->options['container'] === false) {
            return $content;
        }

        // Génère le container
        $tag = $this->options['container.tag'];
        $attributes = $this->getContainerAttributes();

        return $this->renderTag($tag, $attributes, $content);
    }

    /**
     * Retourne les attributs à générer pour le tag ouvrant du container.
     *
     * @return string[]
     */
    protected function getContainerAttributes()
    {
        // Initialise les variables dont on a besoin
        $field = $this->getParameter('field');
        $searchUrl = $this->getSearchRequest()->getSearchUrl();
        $attributes = [];

        // Détermine les classes css à appliquer au container
        $attributes['class'] = trim(sprintf('%s %s %s %s',
            $this->options['container.css'],                  // Classes css indiquées dans les options
            $this->getType(),                                       // Type de la facette (e.g. "terms")
            strtr($field, '.', '-'),                                // Champ sur lequel porte l'agrégation
            $searchUrl->hasFilter($field) ? 'facet-active' : ''     // "facet-active" si l'une des valeurs est filtrée
        ));

        // Génère un attribut 'title' si on a une option 'container.tooltip'
        if ($title = $this->options['container.tooltip']) {
            $attributes['title'] = $title;
        }

        // Génère un attribut 'data-hits' si l'option 'data' est activée
        if ($this->options['data']) {
            $attributes['data-hits'] = $this->getSearchResponse()->getHitsCount();
        }

        // Retourne les attributs
        return $attributes;
    }

    /**
     * Génère le titre de l'agrégation.
     *
     * @return string
     */
    protected function renderTitle()
    {
        // Si on n'a aucun titre (ou false), terminé
        if (empty($title = $this->options['title'])) {
            return '';
        }

        // Génère le titre
        $tag = $this->options['title.tag'];
        $class = $this->options['title.css'];

        return $this->renderTag($tag, $class ? ['class' => $class] : [], $title);
    }

    /**
     * Génère le bloc contenu de l'agrégation.
     *
     * @return string
     */
    protected function renderContent()
    {
        // On ne génère rien si on n'a pas de résultat (ou si l'agrégation ne l'affiche pas : exemple metric à 0)
        if (is_null($this->result) || '' === $result = $this->renderResult()) {
            return '';
        }

        // Génère le contenu avec le tag indiqué dans les options
        $tag = $this->options['content.tag'];
        $class = $this->options['content.css'];

        return $this->renderTag($tag, $class ? ['class' => $class] : [], $result);
    }

    /**
     * Génère le résultat de l'agrégation.
     *
     * @return string
     */
    protected function renderResult()
    {
        // Génère un dump json du résultat
        return json_encode($this->result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Génère un tag html.
     *
     * @param string    $tag        Nom du tag à générer.
     * @param array     $attributes Attributs du tag.
     * @param string    $content    Contenu du tag.
     *
     * @return string
     */
    protected function renderTag($tag, array $attributes = [], $content = null)
    {
        ob_start();
        docalist('html')->tag($tag, $attributes, $content);

        return ob_get_clean();
    }
}
