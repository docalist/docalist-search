<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2011-2016 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search\QueryParser;

/**
 * Interface des "builders" utilisés par le query-parser.
 *
 * La classe QueryParser ne construit pas directement la requête elasticsearch : elle utilise un objet builder et
 * appelle les différentes méthodes au fur et à mesure de l'analyse.
 *
 * Actuellement, on a deux Builders différents :
 * - QueryBuilder qui construit une requête elasticsearch. C'est ce qui est utilisé quand on appelle la méthode parse()
 *   du QueryParser.
 * - ExplainBuilder qui construit une équation de recherche permettant de voir comment la requête a été interprétée.
 *   C'est ce qui est utilisé quand on appelle la méthode explain() du QueryParser.
 *
 * Cette interface définit les différentes méthodes que doivent implémenter les builders.
 */
interface Builder
{
    /**
     * Construit une requête "match"
     *
     * @param string $field Le champ sur lequel porte la recherche
     * @param array $terms Liste des termes
     *
     * @return array
     */
    public function match($field, array $terms);

    /**
     * Construit une requête "match phrase"
     *
     * @param string $field Le champ sur lequel porte la recherche
     * @param array $terms Liste des termes
     *
     * @return array
     */
    public function phrase($field, array $terms);

    /**
     * Construit une requête "prefix"
     *
     * @param string $field Le champ sur lequel porte la recherche
     * @param array $prefix Liste des termes
     *
     * @return array
     */
    public function prefix($field, $prefix);

    /**
     * Construit une requête "match all"
     *
     * @return array
     */
    public function all();

    /**
     * Construit une requête "exists"
     *
     * @return array
     */
    public function exists($field);

    /**
     * Construit une requête booléenne.
     *
     * @param array $should
     * @param array $must
     * @param array $not
     *
     * @return array
     */
    public function bool(array $should = [], array $must = [], array $not = []);

    /**
     * Construit une requête range.
     *
     * @param string $field
     * @param string $start
     * @param string $end
     */
    public function range($field, $start, $end);
}
