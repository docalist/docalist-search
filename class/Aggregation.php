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
     * Retourne le type d'agrégation.
     *
     * @return string Le type de l'agrégation (identifiant elasticsearch)
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

    /**
     * Stocke l'objet SearchRequest qui a créé cette aggrégation.
     *
     * @param SearchRequest $searchRequest
     *
     * @eturn self
     */
    public function setSearchRequest(SearchRequest $searchRequest);

    /**
     * Retourne l'objet SearchRequest qui a créé cette aggrégation.
     *
     * @return SearchRequest
     */
    public function getSearchRequest();

    /**
     * Définit la vue utilisée pour afficher l'aggrégation.
     *
     * @param string $view
     *
     * @return self
     */
    public function setView($view);

    /**
     * Retourne la vue utilisée pour afficher l'aggrégation.
     *
     * @return string La vue indiquée lors du dernier appel à setView() ou la vue par défaut de l'agrégation si
     * aucune vue n'a été définie.
     */
    public function getView();

    /**
     * Définit les données à transmettre à la vue lors de l'affichage.
     *
     * @param array $data Les données qui seront transmises à la vue lorsque display() ou render() seront appelées.
     *
     * @return self
     */
    public function setViewData(array $data);

    /**
     * Retourne les données à transmettre à la vue lors de l'affichage.
     *
     * @return array|null
     */
    public function getViewData();

    /**
     * Affiche le résultat de l'aggrégation.
     *
     * L'affichage est effectué en appelant le service 'views' de docalist avec la vue retournée par getView() et les
     * paramètres fournis par getViewData().
     *
     * @return mixed La méthode retourne ce que retourne la vue (rien en général).
     */
    public function display();

    /**
     * Identique à display() mais retourne le résultat au lieu de l'afficher.
     *
     * @return string
     */
    public function render();
}
