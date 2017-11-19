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

use Docalist\Search\QueryDSL;
use Docalist\Search\IndexManager;

/**
 * Gère les types de contenus sur lesquels porte la clause query de la requête.
 */
trait TypesTrait
{
    /**
     * Liste des contenus sur lesquels portera la recherche.
     *
     * @var string[]
     */
    protected $types;

    /**
     * Retourne la liste des types de contenus sur lesquels porte la recherche.
     *
     * @return string[]
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * Définit la liste des contenus sur lesquels porte la recherche.
     *
     * @param array $types
     *
     * @return self
     */
    public function setTypes(array $types = [])
    {
        if (empty($types)) {
            $indexManager = docalist('docalist-search-index-manager'); /** @var IndexManager $indexManager */
            $types = $indexManager->getTypes();
        }

        $this->types = $types;

        return $this;
    }

    protected function getTypesFilterClause()
    {
        // Rien à faire si on a aucun type
        if (empty($this->types)) {
            return [];
        }

        $indexManager = docalist('docalist-search-index-manager'); /** @var IndexManager $indexManager */
        $dsl = docalist('elasticsearch-query-dsl'); /** @var QueryDSL $dsl */

        // Retourne une clause simple si on a un seul filtre
        if (count($this->types) === 1) {
            $type = reset($this->types);
            return $dsl->filter($indexManager->getIndexer($type)->getSearchFilter());
        }

        // Si on a plusieurs filtres, on les combine dans une clause "bool"
        $filters = [];
        foreach ($this->types as $type) {
            $filter = $indexManager->getIndexer($type)->getSearchFilter();
            $filters[] = $dsl->should($filter);
        }

        return $dsl->filter($dsl->bool($filters));
    }
}
