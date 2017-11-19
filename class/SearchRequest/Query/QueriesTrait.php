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
namespace Docalist\Search\SearchRequest\Query;

/**
 * Gère les recherches sur lesquelles porte la clause query de la requête.
 */
trait QueriesTrait
{
    /**
     * Liste des requêtes qui composent la recherche.
     *
     * @var array
     */
    protected $queries = [];

    /**
     * Ajoute une requête à la liste des requêtes qui composent la recherche.
     *
     * @param array $query Un tableau décrivant la requête, en général créé avec le service QueryDSL. Exemple :
     *
     * <code>
     * $request->addQuery($dsl->match('title', 'hello world!')
     * </code>
     *
     * @return self
     */
    public function addQuery(array $query)
    {
        $this->queries[] = $query;

        return $this;
    }

    /**
     * Définit la liste des requêtes qui composent la recherche.
     *
     * @param array[] $queries Un tableau de requêtes.
     *
     * Chaque requête est elle-même un tableau, en général créée avec le service QueryDSL. Exemple :
     *
     * <code>
     * $request->setQueries([
     *     $dsl->match('title', 'hello'),
     *     $dsl->match('content', 'world'),
     * ]);
     * </code>
     *
     * Si la méthode est appelée sans arguments ou avec un tableau vide, la liste des requêtes est réinitialisée.
     *
     * @return self
     */
    public function setQueries(array $queries = [])
    {
        $this->queries = [];
        foreach ($queries as $query) {
            $this->addQuery($query);
        }

        return $this;
    }

    /**
     * Indique si la recherche contient des requêtes.
     *
     * @return bool
     */
    public function hasQueries()
    {
        return !empty($this->queries);
    }

    /**
     * Retourne les requêtes qui composent la recherche.
     *
     * @return array[] Un tableau de requêtes.
     */
    public function getQueries()
    {
        return $this->queries;
    }
}
