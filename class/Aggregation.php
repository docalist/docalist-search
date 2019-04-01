<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Search;

use Docalist\Search\SearchRequest;
use stdClass;

/**
 * Interface commune à toutes les agrégations.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
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
     * Remarque : chaque agrégation doit avoir un nom (unique) qui permet de l'identifier dans la liste des
     * agrégations et par défaut il s'agit de la classe de l'objet agrégation.
     *
     * @param string $name Nom de l'agrégation.
     *
     * @return self
     */
    public function setName($name);

    /**
     * Retourne le nom de l'agrégation.
     *
     * @return string Le nom de l'agrégation (ou sa classe si aucun nom n'a été défini).
     */
    public function getName();

    /**
     * Définit les paramètres de l'agrégation.
     *
     * @param array $parameters Un tableau contenant les paramètres elasticsearch de l'agrégation.
     *
     * Remarque : aucun test n'est fait pour vérifier la validité des paramètres fournis. S'ils sont erronés,
     * elasticsearch générera une erreur.
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
     * Définit le résultat de l'agrégation.
     *
     * @param stdClass $result Le résultat généré par elasticsearch pour cette agrégation.
     *
     * @return self
     */
    public function setResult(stdClass $result);

    /**
     * Retourne le résultat de l'agrégation.
     *
     * Par défaut, la méthode retourne le résultat de l'agrégation tel qu'il a été définit lors du dernier appel
     * à setResult().  Si un champ est indiqué en paramètre et que ce champ figure dans les résultats, la méthode
     * retourne le contenu de ce champ.
     *
     * Par exemple : si le résultat de l'agrégation est un objet de la forme "{count: 44, min: 1, max:40, etc.}",
     * getResult() retournera cet objet complet et getResult('count') retournera 44.
     *
     * @param string|null $name Optionnel, nom du champ de résultat à retourner.
     *
     * @return mixed|null Le résultat demandé ou null s'il n'est pas disponible.
     */
    public function getResult($name = null);

    /**
     * Définit l'objet SearchRequest dans lequel figure cette agrégation.
     *
     * @param SearchRequest $searchRequest
     *
     * @return self
     */
    public function setSearchRequest(SearchRequest $searchRequest = null);

    /**
     * Retourne l'objet SearchRequest dans lequel figure cette agrégation.
     *
     * @return SearchRequest
     */
    public function getSearchRequest();

    /**
     * Définit l'objet SearchResponse qui contient les résultats de cette agrégation.
     *
     * @param SearchResponse $searchResponse
     *
     * @return self
     */
    public function setSearchResponse(SearchResponse $searchResponse);

    /**
     * Retourne l'objet SearchResponse qui contient les résultats de cette agrégation.
     *
     * @return SearchResponse
     */
    public function getSearchResponse();

    /**
     * Retourne les options d'affichage par défaut.
     *
     * @return array
     */
    public function getDefaultOptions();

    /**
     * Définit les options d'affichage.
     *
     * Les options passées en paramètre sont fusionnées avec les options d'affichage déjà définies.
     *
     * @param array $options
     *
     * @return self
     */
    public function setOptions(array $options = []);

    /**
     * Retourne les options d'affichage.
     *
     * @return array
     */
    public function getOptions();

    /**
     * Définit une option d'affichage.
     *
     * @param string    $option Nom de l'option.
     * @param mixed     $value  Nouvelle valeur de l'option.
     *
     * @return self
     */
    public function setOption($option, $value);

    /**
     * Retourne la valeur actuelle d'une option d'affichage.
     *
     * @param string $option Nom de l'option à retourner.
     *
     * @return mixed|null La valeur de l'option demandée ou null si l'option n'est pas définie.
     */
    public function getOption($option);

    /**
     * Affiche l'agrégation.
     *
     * @param array $options Options d'affichage (fusionnées avec les options en cours).
     *
     * @return self
     */
    public function display(array $options = []);

    /**
     * Identique à display() mais retourne le résultat au lieu de l'afficher.
     *
     * @param array $options Options d'affichage (fusionnées avec les options en cours).
     *
     * @return string
     */
    public function render(array $options = []);

    /**
     * Indique si l'agrégation est active.
     *
     * L'agrégation est active si la requête en cours contient un filtre qui porte sur le champ utilisé par
     * l'agrégation.
     *
     * @return bool
     */
    public function isActive();
}
