<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search\Lookup;

use Docalist\Lookup\LookupInterface;

/**
 * Lookup sur les titres des posts et des notices.
 *
 * Permet de récupérer le POST ID d'une notice dont on indique le début du titre.
 * Utilisé pour les lookups sur les champs de type Relation.
 */
class SearchLookup implements LookupInterface
{
    public function hasMultipleSources()
    {
        return true;
    }

    public function getCacheMaxAge()
    {
        return 0; // Pas de cache
    }

    public function getDefaultSuggestions($source = '')
    {
        // On récupère le titre des notices qui ont été mises à jour récemment
        $query = [
            'size' => 100,
            '_source' => [ 'title' ],
            'query' => [
                'bool' => [
                    'filter'    => [ [ 'query_string' => [ 'query' => $source ] ] ],
                ]
            ],
            'sort' => [ 'lastupdate' => 'desc' ],
        ];

        // Exécute la requête
        $response = docalist('elastic-search')->post('/{index}/_search', $query);

        // Retourne les résultats
        return $this->processResponse($response);
    }

    public function getSuggestions($search, $source = '')
    {
        // On recherche les suggestions sur le champ "title" uniquement en utilisant une match_phrase_prefix
        // La source (obligatoire) indique le filtre qui est appliqué à la recherche (query-string avec default_op=OR)
        // cf. https://www.elastic.co/guide/en/elasticsearch/reference/master/query-dsl-match-query.html#query-dsl-match-query-phrase-prefix
        $query = [
            'size' => 100,
            '_source' => [ 'title' ],
            'query' => [
                'bool' => [
                    'must'   => [ [ 'match_phrase_prefix' => [ 'title' => $search ] ] ],
                    'filter' => [ [ 'query_string'        => [ 'query' => $source ] ] ],
                ]
            ],
        ];


        // Exécute la requête
        $response = docalist('elastic-search')->post('/{index}/_search', $query);

        // Retourne les résultats
        return $this->processResponse($response);
    }

    /**
     * Convertit la réponse elastic search en tableau de suggestions.
     *
     * @param object $response
     * @return array
     */
    protected function processResponse($response)
    {
        // Aucune réponse ?
        if (! isset($response->hits->hits)) {
            return [];
        }

        // Construit un tuple (code,label) pour chaque hit obtenu
        $result = [];
        foreach($response->hits->hits as $hit) {
            $result[] = [
                'code' => $hit->_id,
                'label' => $hit->_source->title ?: ('ID #' . $hit->_id),
            ];

        }

        // Ok
        return $result;
    }
}
