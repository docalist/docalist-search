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
namespace Docalist\Search\Mapping;

/**
 * Classe de base pour les champs qui composent un mapping docalist-search.
 */
abstract class Field
{
    /**
     * Le nom du champ.
     *
     * @var string
     */
    protected $name;

    /**
     * Les paramètres du champ.
     *
     * @var array
     */
    protected $parameters;

    /**
     * Initialise le champ.
     *
     * @param string    $name       Nom du champ.
     * @param array     $parameters Optionnel, paramètres du champ à fusionner avec les paramètres par défaut.
     */
    public function __construct($name, array $parameters = [])
    {
        $this->name = $name;
        $this->parameters = array_merge($this->getDefaultParameters(), $parameters);
    }

    /**
     * Retourne les paramètres par défaut du champ.
     *
     * @return array
     */
    public function getDefaultParameters()
    {
        return [];
    }

    /**
     * Retourne le nom du champ.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Retourne les paramètres du champ.
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Génère le mapping du champ.
     *
     * @return array
     */
    public function getMapping()
    {
        return $this->parameters;
    }

    /*
     * Options
     *
     * - search / searchable
     * - filter / filterable
     * - sort / sortable
     * - spellcheck
     * - suggest / suggestable
     * - weight
     */

}
