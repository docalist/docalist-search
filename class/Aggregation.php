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

use Docalist\Search\SearchRequest2 as SearchRequest;

/**
 * Interface commune à toutes les agrégations.
 */
interface Aggregation
{
    /**
     * Retourne le type de l'agrégation.
     *
     * @return string Le type de l'agrégation (identifiant elasticsearch).
     */
    public function getType();

    /**
     * Définit le nom de l'agrégation.
     *
     * @param string $name Nom de l'agrégation.
     *
     * @return self
     */
    public function setName($name);

    /**
     * Retourne le nom de l'agrégation.
     *
     * @return string|null Le nom de l'agrégation ou null si l'agrégation n'a pas de nom.
     */
    public function getName();

    /**
     * Définit le titre de l'agrégation.
     *
     * @param string $title Le titre de l'agrégation.
     *
     * @return self
     */
    public function setTitle($title);

    /**
     * Retourne le titre de l'agrégation.
     *
     * @return string Le titre de l'agrégation ou null si l'agrégation n'a pas de titre.
     */
    public function getTitle();

    /**
     * Définit les paramètres de l'agrégation.
     *
     * @param array $parameters Un tableau contenant les paramètres elasticsearch de l'agrégation.
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
     * @param string $name  Nom du paramètre à modifier.
     * @param mixed  $value Valeur à associer.
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
     * Stocke les résultats bruts de l'agrégation.
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

    /**
     * Stocke l'objet SearchRequest qui a créé cette agrégation.
     *
     * @param SearchRequest $searchRequest
     *
     * @eturn self
     */
    public function setSearchRequest(SearchRequest $searchRequest);

    /**
     * Retourne l'objet SearchRequest qui a créé cette agrégation.
     *
     * @return SearchRequest
     */
    public function getSearchRequest();

    /**
     * Retourne le nom de la vue par défaut utilisée pour afficher les résultats de cette agrégation.
     *
     * @return string
     */
    public function getDefaultView();

    /**
     * Affiche le résultat de l'agrégation.
     *
     * La méthode peut être appellée avec 0, 1 ou 2 paramètres :
     *
     * - display() : affichage par défaut.
     * - display([...]) ou display(null, [...]) : affichage par défaut avec les paramètres fournis.
     * - display('ma-vue') : affichage avec la vue indiquée.
     * - display('ma-vue', [...]) : affichage avec la vue indiquée et les paramètres fournis.
     * - display(null, [...]) : exécute la vue indiquée en lui fournissant les paramètres indiqués.
     *
     * @param string $view Optionnel, le nom de la vue à exécuter (vue par défaut de l'agrégation sinon).
     * @param array  $data Optionnel, un tableau contenant les données à transmettre à la vue.
     *
     * @return mixed La méthode retourne ce que retourne la vue (rien en général).
     */
    public function display($view = null, array $data = []);

    /**
     * Identique à display() mais retourne le résultat au lieu de l'afficher.
     *
     * @param string $view Optionnel, le nom de la vue à exécuter (vue par défaut de l'agrégation sinon).
     * @param array  $data Optionnel, un tableau contenant les données à transmettre à la vue.
     *
     * @return string
     */
    public function render($view = null, array $data = []);
}
