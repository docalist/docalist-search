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

/**
 * Interface commune à toutes les agrégations.
 */
interface Aggregation
{
    /**
     * Retourne le type d'agrégation.
     *
     * @return string Le type de l'agrégation (identifiant elasticsearch)
     */
    public function getType();

    /**
     * Définit les paramètres de l'agrégation.
     *
     * @param array $parameters
     *
     * @return self
     */
    public function setParameters(array $parameters);

    /**
     * Retourne les paramètres de l'agrégation.
     *
     * @return array
     */
    public function getParameters();

    /**
     * Définit un paramètre de l'agrégation.
     *
     * @param string $name Nom du paramètre à modifier.
     * @param mixed $value Valeur à associer.
     *
     * @return self
     */
    public function setParameter($name, $value);

    /**
     * Retourne la valeur d'un paramètre de l'agrégation.
     *
     * @param string $name Nom du paramètre à retourner.
     *
     * @return mixed La valeur du paramètre ou null si le paramètre n'est pas définit.
     */
    public function getParameter($name);

    /**
     * Indique si le paramètre indiqué existe dans l'agrégation.
     *
     * @param string $name Nom du paramètre à tester.
     *
     * @return bool
     */
    public function hasParameter($name);

    /**
     * Retourne la définition de l'agrégation.
     *
     * @return array Un tableau DSL décrivant l'agrégation.
     */
    public function getDefinition();

    /**
     * Stocke les résultats de l'agrégation.
     *
     * @param object $results
     *
     * @return self
     */
    public function setResults($results);

    /**
     * Retourne les résultats bruts de l'agrégation.
     *
     * @return object
     */
    public function getResults();
}
