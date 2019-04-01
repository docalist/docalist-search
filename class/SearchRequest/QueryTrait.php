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

namespace Docalist\Search\SearchRequest;

use Docalist\Search\QueryDSL;
use Docalist\Search\SearchRequest\Query\TypesTrait;
use Docalist\Search\SearchRequest\Query\QueriesTrait;
use Docalist\Search\SearchRequest\Query\FiltersTrait;

/**
 * Gère les clauses 'query' et 'post_filter' de la requête.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
trait QueryTrait
{
    use TypesTrait, QueriesTrait, FiltersTrait;

    /**
     * Stocke la clause "query" à exécuter dans la requête qui sera envoyée à Elasticsearch.
     *
     * @param array $request Le tableau contenant la requête à modifier.
     */
    public function buildQueryClause(array & $request)
    {
        // remarque : initialement, la méthode était "protected"
        // elle a été rendue "public" pour permettre à TermsAggregation de gérer l'option "multiselect"
        $dsl = docalist('elasticsearch-query-dsl'); /* @var QueryDSL $dsl */
        $clauses = [];

        // Queries
        foreach ($this->getQueries() as $query) {
            $clauses[] = $dsl->must($query);
        }

        // User filters
        foreach ($this->getFilters('user-filter') as $filter) {
            $clauses[] = $dsl->filter($filter);
        }

        // Crée le filtre permettant de limiter la recherche aux types de contenus indiqués : type1 OR type2...
        $clause = $this->getTypesFilterClause();
        !empty($clause) && $clauses[] = $clause;

        // Global Filters
        foreach ($this->getFilters('hidden-filter') as $filter) {
            $clauses[] = $dsl->filter($filter);
        }

        // Génère une requête matchAll() si on n'a ni requête ni filtre
        if (empty($clauses)) {
            $request['query'] = $dsl->matchAll();

            return;
        }

        // Si on a plusieurs clauses, on les combine avec une requête bool : {'must'=>queries, 'filter'=>filters}
        if (count($clauses) > 1) {
            $request['query'] = $dsl->bool($clauses);

            return;
        }

        // Si on a seulement une requête ou seulement un filtre, inutile de créer une clause bool
        $clauses = reset($clauses); // On obtient une clause de la forme "must" => []
        $request['query'] = reset($clauses); // On obtient la query qui figure dans la clause
    }

    /**
     * Stocke la clause "post_filter" à exécuter dans la requête qui sera envoyée à Elasticsearch.
     *
     * @param array $request Le tableau contenant la requête à modifier.
     */
    protected function buildPostFilterClause(array & $request)
    {
        // Si on n'a aucun post-filter, terminé
        if (!$this->hasFilters('post-filter')) {
            return;
        }

        // Ajoute tous les filtres
        $request['post_filter'] = [
            'bool' => [
                'filter' => $this->getFilters('post-filter')
            ]
        ];
/*
        $clauses = [];
        foreach ($this->getFilters('post-filter') as $filter) {
            $clauses[] = $dsl->filter($filter);
        }

        if (count($clauses) === 1) {
            $clauses = reset($clauses); // On obtient une clause de la forme "must" => []
            $request['post_filter'] = reset($clauses); // On obtient la query qui figure dans la clause
        } else {
            $request['post_filter'] = $dsl->bool($clauses);
        }
        $request['post_filter'] = $this->assemble($clauses);
*/
    }
}
