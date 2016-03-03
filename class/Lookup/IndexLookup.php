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
 * Lookup sur la liste des termes présents dans l'index Ealsticsearch.
 *
 * Le champ doit être indexé comme filtre (filter) et comme champ de completion (suggest).
 */
class IndexLookup implements LookupInterface
{
    public function hasMultipleSources()
    {
        return true;
    }

    public function getCacheMaxAge()
    {
        return 10 * MINUTE_IN_SECONDS; // peut changer à chaque enregistrement de notice (candidat descripteurs, etc.)
    }

    public function getDefaultSuggestions($source = '')
    {
        // https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations-bucket-terms-aggregation.html
        $query = [
            'aggs' => [
                'lookup' => [
                    'terms' => [
                        'field' => "$source.filter",
                        'size' => 100,
                        'order' => ['_term' => 'asc'],
//                      'missing' => 'zzzz',
                    ],
                ],
            ],
        ];

        // Exécute la requête
        $result = docalist('elastic-search')->post('/{index}/_search?search_type=count', $query);
        if (! isset($result->aggregations->lookup->buckets)) {
            return [];
        }

        $result = $result->aggregations->lookup->buckets;
        foreach ($result as $bucket) {
            $bucket->text = $bucket->key;
            unset($bucket->key);

            $bucket->score = $bucket->doc_count;
            unset($bucket->doc_count);
        }

        return $result;
    }

    public function getSuggestions($search, $source = '')
    {
        // @see https://www.elastic.co/guide/en/elasticsearch/reference/master/search-suggesters-completion.html
        $query = [
            'lookup' => [
                'text' => $search,
                'completion' => [
                    'field' => "$source.suggest",
                    'size' => 100,
                    // 'fuzzy' => true
                    'prefix_len' => 1,
                ],
            ],
        ];

        // Exécute la requête
        $result = docalist('elastic-search')->post('/{index}/_suggest', $query);

        // Récupère les suggestions
        if (! isset($result->lookup[0]->options)) {
            return [];
        }

        // Ok. Résultat de la forme suivante : [{"text":"artwork","score":1},{"text":"artistic","score":1}]
        return $result->lookup[0]->options;
    }
}
