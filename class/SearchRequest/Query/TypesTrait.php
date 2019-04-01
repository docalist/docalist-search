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

namespace Docalist\Search\SearchRequest\Query;

use Docalist\Search\QueryDSL;
use Docalist\Search\IndexManager;

/**
 * Gère les types de contenus sur lesquels porte la clause query de la requête.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
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
            $indexManager = docalist('docalist-search-index-manager'); /* @var IndexManager $indexManager */
            $types = $indexManager->getTypes();
        }

        $this->types = $types;

        return $this;
    }

    /**
     * Retourne la clause DSL filter permettant de restreindre la recherche aux types sélectionnés.
     *
     * @return array
     */
    protected function getTypesFilterClause()
    {
        // Rien à faire si on a aucun type
        if (empty($this->types)) {
            return [];
        }

        $dsl = docalist('elasticsearch-query-dsl'); /* @var QueryDSL $dsl */

        // Retourne une clause simple si on a un seul filtre
        if (count($this->types) === 1) {
            $filter = $this->getTypeQuery(reset($this->types));

            return $dsl->filter($filter);
        }

        // Si on a plusieurs filtres, on les combine dans une clause "bool"
        $filters = [];
        foreach ($this->types as $type) {
            $filter = $this->getTypeQuery($type);
            $filters[] = $dsl->should($filter);
        }

        return $dsl->filter($dsl->bool($filters));
    }

    /**
     * Convertit le nom de type passé en paramètre en requête ElasticSearch.
     *
     * Le type indiqué peut être soit le nom d'un post type indexé ("post", "page", etc.) soit un "pseudo type".
     *
     * S'il s'agit d'un post-type, la méthode récupère l'indexeur associé et appelle la méthode getSearchFilter()
     * pour récupérer la requête à utiliser.
     *
     * Sinon, la méthode déclenche le filtre "docalist_search_type_query". Si quelqu'un répond et génère une
     * requête, celle-ci est retournée telle quelle (par exemple le module basket retourne une clause ids qui
     * liste tous les documents sélectionnés).
     *
     * Si aucune requête ne peut être générée pour le type indiqué, la méthode retourne une requête matchNone() qui
     * aura pour effet de générer une recherche "zéro réponses".
     *
     * @param string $type Le type recherché.
     *
     * @return array La clause ElasticSearch générée.
     */
    private function getTypeQuery(string $type)
    {
        // Teste s'il s'agit d'un post_type indexé
        $indexManager = docalist('docalist-search-index-manager'); /* @var IndexManager $indexManager */
        $indexers = $indexManager->getAvailableIndexers();
        if (isset($indexers[$type])) {
            return $indexers[$type]->getSearchFilter();
        }

        // Génère l'événement "docalist_search_type_query" et retourne la requête générée
        $filter = apply_filters('docalist_search_type_query', [], $type);
        if (! empty($filter)) {
            return $filter;
        }

        // Nom de type inconnu, génère une requête matchNone
        $dsl = docalist('elasticsearch-query-dsl'); /* @var QueryDSL $dsl */
        return $dsl->matchNone();
    }
}
