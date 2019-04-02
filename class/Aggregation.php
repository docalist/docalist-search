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
    public function getType(): string;

    /**
     * Définit le nom de l'agrégation.
     *
     * Chaque agrégation doit avoir un nom (unique) qui permet de l'identifier dans la liste des agrégations de la
     * requête.
     *
     * @param string $name Nom de l'agrégation.
     */
    public function setName(string $name): void;

    /**
     * Retourne le nom de l'agrégation.
     *
     * @return string Le nom de l'agrégation.
     */
    public function getName(): string;

    /**
     * Définit les paramètres de l'agrégation.
     *
     * @param array $parameters Un tableau contenant les paramètres elasticsearch de l'agrégation.
     *
     * Remarque : aucun test n'est fait pour vérifier la validité des paramètres fournis. S'ils sont erronés,
     * elasticsearch générera une erreur.
     */
    public function setParameters(array $parameters): void;

    /**
     * Retourne les paramètres de l'agrégation.
     *
     * @return array
     */
    public function getParameters(): array;

    /**
     * Définit un paramètre de l'agrégation.
     *
     * @param string $name  Nom du paramètre à modifier.
     * @param mixed  $value Valeur à associer.
     */
    public function setParameter(string $name, $value): void;

    /**
     * Retourne la valeur d'un paramètre de l'agrégation.
     *
     * @param string $name Nom du paramètre à retourner.
     *
     * @return mixed La valeur du paramètre ou null si le paramètre n'est pas définit.
     */
    public function getParameter(string $name);

    /**
     * Indique si le paramètre indiqué existe dans l'agrégation.
     *
     * @param string $name Nom du paramètre à tester.
     *
     * @return bool
     */
    public function hasParameter(string $name): bool;

    /**
     * Retourne la définition de l'agrégation.
     *
     * @return array Un tableau DSL décrivant l'agrégation.
     */
    public function getDefinition(): array;

    /**
     * Définit le résultat de l'agrégation.
     *
     * @param stdClass $result Le résultat généré par elasticsearch pour cette agrégation.
     */
    public function setResult(stdClass $result): void;

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
     * @param SearchRequest|null $searchRequest
     */
    public function setSearchRequest(?SearchRequest $searchRequest): void;

    /**
     * Retourne l'objet SearchRequest dans lequel figure cette agrégation.
     *
     * @return SearchRequest|null
     */
    public function getSearchRequest(): ?SearchRequest;

    /**
     * Définit l'objet SearchResponse qui contient les résultats de cette agrégation.
     *
     * @param SearchResponse|null $searchResponse
     */
    public function setSearchResponse(?SearchResponse $searchResponse): void;

    /**
     * Retourne l'objet SearchResponse qui contient les résultats de cette agrégation.
     *
     * @return SearchResponse|null
     */
    public function getSearchResponse(): ?SearchResponse;

    /**
     * Retourne les options d'affichage par défaut.
     *
     * @return array
     */
    public function getDefaultOptions(): array;

    /**
     * Définit les options d'affichage.
     *
     * Les options passées en paramètre sont fusionnées avec les options d'affichage déjà définies.
     *
     * @param array $options
     */
    public function setOptions(array $options = []): void;

    /**
     * Retourne les options d'affichage.
     *
     * @return array
     */
    public function getOptions(): array;

    /**
     * Définit une option d'affichage.
     *
     * @param string    $option Nom de l'option.
     * @param mixed     $value  Nouvelle valeur de l'option.
     */
    public function setOption(string $option, $value): void;

    /**
     * Retourne la valeur actuelle d'une option d'affichage.
     *
     * @param string $option Nom de l'option à retourner.
     *
     * @return mixed|null La valeur de l'option demandée ou null si l'option n'est pas définie.
     */
    public function getOption(string $option);

    /**
     * Affiche l'agrégation.
     *
     * @param array $options Options d'affichage (fusionnées avec les options en cours).
     */
    public function display(array $options = []): void;

    /**
     * Identique à display() mais retourne le résultat au lieu de l'afficher.
     *
     * @param array $options Options d'affichage (fusionnées avec les options en cours).
     *
     * @return string
     */
    public function render(array $options = []): string;

    /**
     * Indique si l'agrégation est active.
     *
     * L'agrégation est active si la requête en cours contient un filtre qui porte sur le champ utilisé par
     * l'agrégation.
     *
     * @return bool
     */
    public function isActive(): bool;
}
