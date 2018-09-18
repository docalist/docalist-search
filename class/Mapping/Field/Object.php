<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2012-2016 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search\Mapping\Field;

use Docalist\Search\Mapping\Field;
use InvalidArgumentException;

/**
 * Un champ structuré contenant d'autres champs.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/object.html
 */
class Object extends Field
{
    /**
     * L'analyseur par défaut à utiliser pour les champs de type 'text'.
     *
     * @var string
     */
    protected $defaultTextAnalyzer = 'text';

    /**
     * Retourne le nom de l'analyseur par défaut utilisé pour les champs de type 'text'.
     *
     * L'analyseur par défaut est utilisé par la méthode {@link text()} lorsqu'aucun analyseur n'est indiqué.
     *
     * @return string Le nom de l'analyseur par défaut ('text', 'fr-text', 'en-text'...)
     */
    public function getDefaultTextAnalyzer()
    {
        return $this->defaultTextAnalyzer;
    }

    /**
     * Définit l'analyseur par défaut utilisé pour les champs de type 'text'.
     *
     * L'analyseur par défaut est utilisé par la méthode {@link text()} lorsqu'aucun analyseur n'est indiqué.
     *
     * @param string $defaultAnalyzer Le nom de l'analyseur par défaut à utiliser ('text', 'fr-text', 'en-text'...)
     *
     * @return self
     */
    public function setDefaultTextAnalyzer($defaultTextAnalyzer)
    {
        $this->defaultTextAnalyzer = $defaultTextAnalyzer;

        return $this;
    }

    public function getDefaultParameters()
    {
        return [
            'type' => 'object',
//            'properties' => [],
        ];
    }

    /**
     * Ajoute un champ.
     *
     * @param Field $field Champ à ajouter.
     *
     * @return self
     *
     * @throws InvalidArgumentException S'il existe déjà un champ avec ce nom.
     */
    public function add(Field $field)
    {
        // Récupère le nom du champ à ajouter
        $name = $field->getName();

        // S'il existe déjà un champ avec ce nom, erreur
        if (isset($this->parameters['properties'][$name])) {
            throw new InvalidArgumentException("A field named '$name' already exists in '". $this->getName() . "'");
        }

        // Ok
        $this->parameters['properties'][$name] = $field;

        return $this;
    }

    /**
     * Ajoute une champ de type 'binary'.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/binary.html
     *
     * @param string    $name       Nom du champ
     * @param array     $parameters Optionnel, paramètres du champ à fusionner avec les paramètres par défaut.
     *
     * @return Binary
     */
    public function binary($name, array $parameters = [])
    {
        $this->add($field = new Binary($name, $parameters));

        return $field;
    }

    /**
     * Ajoute un champ de type 'boolean'
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/boolean.html
     *
     * @param string    $name       Nom du champ
     * @param array     $parameters Optionnel, paramètres du champ à fusionner avec les paramètres par défaut.
     *
     * @return Boolean
     */
    public function boolean($name, array $parameters = [])
    {
        $this->add($field = new Boolean($name, $parameters));

        return $field;
    }

    /**
     * Ajoute un champ de type 'date'.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/date.html
     *
     * @param string    $name       Nom du champ
     * @param array     $parameters Optionnel, paramètres du champ à fusionner avec les paramètres par défaut.
     *
     * @return Date
     */
    public function date($name, array $parameters = [])
    {
        $this->add($field = new Date($name, $parameters));

        return $field;
    }

    /**
     * Ajoute un champ de type 'decimal' ('float' par défaut).
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/number.html
     *
     * @param string    $name       Nom du champ
     * @param array     $parameters Optionnel, paramètres du champ à fusionner avec les paramètres par défaut.
     *
     * @return Decimal
     */
    public function decimal($name, array $parameters = [])
    {
        $this->add($field = new Decimal($name, $parameters));

        return $field;
    }

    /**
     * Ajoute un champ de type 'geo point'.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/geo-point.html
     *
     * @param string    $name       Nom du champ
     * @param array     $parameters Optionnel, paramètres du champ à fusionner avec les paramètres par défaut.
     *
     * @return Geopoint
     */
    public function geopoint($name, array $parameters = [])
    {
        $this->add($field = new Geopoint($name, $parameters));

        return $field;
    }

    /**
     * Ajoute un champ de type 'geo shape'.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/geo-shape.html
     *
     * @param string    $name       Nom du champ
     * @param array     $parameters Optionnel, paramètres du champ à fusionner avec les paramètres par défaut.
     *
     * @return Geoshape
     */
    public function geoshape($name, array $parameters = [])
    {
        $this->add($field = new Geoshape($name, $parameters));

        return $field;
    }

    /**
     * Ajoute un champ de type 'integer'.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/number.html
     *
     * @param string    $name       Nom du champ
     * @param array     $parameters Optionnel, paramètres du champ à fusionner avec les paramètres par défaut.
     *
     * @return Integer
     */
    public function integer($name, array $parameters = [])
    {
        $this->add($field = new Integer($name, $parameters));

        return $field;
    }

    /**
     * Ajoute un champ de type 'ip'.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/ip.html
     *
     * @param string    $name       Nom du champ
     * @param array     $parameters Optionnel, paramètres du champ à fusionner avec les paramètres par défaut.
     *
     * @return IP
     */
    public function ip($name, array $parameters = [])
    {
        $this->add($field = new IP($name, $parameters));

        return $field;
    }

    /**
     * Ajoute un champ de type 'keyword'.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/keyword.html
     *
     * @param string    $name       Nom du champ
     * @param array     $parameters Optionnel, paramètres du champ à fusionner avec les paramètres par défaut.
     *
     * @return Keyword
     */
    public function keyword($name, array $parameters = [])
    {
        $this->add($field = new Keyword($name, $parameters));

        return $field;
    }

    /**
     * Ajoute un champ est de type 'nested'.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/nested.html
     *
     * @param string    $name       Nom du champ
     * @param array     $parameters Optionnel, paramètres du champ à fusionner avec les paramètres par défaut.
     *
     * @return Nested
     */
    public function nested($name, array $parameters = [])
    {
        $field = new Nested($name, $parameters);
        $field->setDefaultTextAnalyzer($this->defaultTextAnalyzer);

        $this->add($field);

        return $field;
    }

    /**
     * Ajoute un champ est de type 'objet'.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/object.html
     *
     * @param string    $name       Nom du champ
     * @param array     $parameters Optionnel, paramètres du champ à fusionner avec les paramètres par défaut.
     *
     * @return Object
     */
    public function object($name, array $parameters = [])
    {
        $field = new Object($name, $parameters);
        $field->setDefaultTextAnalyzer($this->defaultTextAnalyzer);

        $this->add($field);

        return $field;
    }

    /**
     * Ajoute un champ de type 'text'.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/text.html
     *
     * @param string    $name       Nom du champ
     * @param array     $parameters Optionnel, paramètres du champ à fusionner avec les paramètres par défaut.
     *
     * @return Text
     */
    public function text($name, array $parameters = [])
    {
        !isset($parameters['analyzer']) && $parameters['analyzer'] = $this->getDefaultTextAnalyzer();

        $this->add($field = new Text($name, $parameters));

        return $field;
    }

    public function getMapping()
    {
        // Demande à la classe parent de générer le mapping
        $mapping = parent::getMapping();

        // Ajoute les champs de l'objet
        foreach ($mapping['properties'] as &$field) { /* @var Field $field */
            $field = $field->getMapping();
        }

        // Ok
        return $mapping;
    }
}
