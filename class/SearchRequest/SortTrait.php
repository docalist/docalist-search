<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2013-2017 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search\SearchRequest;

use InvalidArgumentException;

/**
 * Gère le tri dans la requête.
 */
trait SortTrait
{
    /**
     * Liste des clauses de tri.
     *
     * @var array|string|null
     */
    protected $sort;

    /**
     * Ce trait ne peut être utilisé que dans une classe qui supporte la méthode getSize().
     *
     * Retourne le nombre de résultats par page (10 par défaut).
     *
     * @return int Un entier >= 0
     */
    abstract public function getSize();

    /**
     * Définit le tri en cours.
     *
     * @param array|string|null $sort Le tri peut être définit de plusieurs manières. Vous pouvez indiquer :
     *
     * - une chaine contenant un nom de tri. Dans ce cas, lors de l'exécution de la requête, la définition
     *   du tri sera récupérée en appelant le filtre docalist_search_get_sort().
     * - un tableau contenant la définition du tri (dans ce cas, elle est utilisée telle quelle).
     * - null pour supprimer le tri en cours.
     *
     * Exemples :
     *
     * <code>
     * // Utilisation d'un tri nommé, le filtre docalist_search_get_sort() doit retourner la définition.
     * setSort('byAuthorAndByDate');
     *
     * // Définition Elasticsearch
     * setSort([
     *     'lastupdate' => ['order' => 'asc'],
     *     'creation' => ['order' => 'asc', 'missing' => '_first'],
     * ]);
     * </code>
     *
     * @return self
     */
    public function setSort($sort)
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Retourne le tri en cours.
     *
     * @return string|array|null Retourne :
     *
     * - le nom du tri en cours (string),
     * - un tableau contenant la définition du tri en cours (array),
     * - null si aucun tri n'a été défini.
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * Retourne le tri par défaut.
     *
     * Cette méthode se content d'invoquer le filtre 'docalist_search_get_default_sort' et retourne le résultat
     * obtenu (par défaut : null).
     *
     * @return string|array|null Retourne :
     *
     * - un nom de tri (string),
     * - un tableau contenant une définition de tri (array),
     * - null si aucun tri par défaut n'a été défini.
     */
    public function getDefaultSort()
    {
        return apply_filters('docalist_search_get_default_sort', null, $this);
    }

    /**
     * Retourne la définition Elasticsearch d'un tri nommé.
     *
     * @param string $sortName Nom du tri
     *
     * @return null|array
     */
    public function getNamedSort($sortName)
    {
        return apply_filters('docalist_search_get_sort', null, $sortName);
    }

    /**
     * Stocke la clause de tri dans la requête qui sera envoyée à Elasticsearch.
     *
     * @param array $request Le tableau contenant la requête à modifier.
     */
    protected function buildSortClause(array & $request)
    {
        // Inutile de générer une clause de tri si la requête ne retourne aucun hits
        if (0 === $this->getSize()) {
            return;
        }

        // Si aucun tri n'a été définit, on utilise le tri par défaut
        if (is_null($this->sort)) {
            $this->setSort($this->getDefaultSort()); // propriété $sort modifiée
        }

        // Si on n'a toujours aucun tri, terminé
        if (is_null($this->sort)) {
            return;
        }

        // Si on a un tri nommé, on le convertit en clauses de tri Elasticsearch
        if (is_string($this->sort)) {
            $request['sort'] =  $this->getNamedSort($this->sort); // propriété $sort inchangée
            return;
        }

        // On a une définition de tri (un tableau), on la retourne
        if (!is_array($this->sort)) {
            throw new InvalidArgumentException('Internal error: sort devrait être un tableau');
        }

        $request['sort'] = $this->sort;
    }
}
