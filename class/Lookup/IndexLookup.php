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

namespace Docalist\Search\Lookup;

use Docalist\Lookup\LookupInterface;
use InvalidArgumentException;

/**
 * Lookup sur la liste des termes présents dans l'index Elasticsearch.
 *
 * Le champ doit être indexé comme filtre (filter) et comme champ de completion (suggest).
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class IndexLookup implements LookupInterface
{
    /**
     * Version de Elasticsearch.
     *
     * @var string
     */
    protected $elasticsearchVersion;

    /**
     * Initialise le service de lookup sur index.
     *
     * @param string $elasticsearchVersion Version de Elasticsearch.
     */
    public function __construct(string $elasticsearchVersion)
    {
        $this->elasticsearchVersion = $elasticsearchVersion;
    }

    /**
     * {@inheritDoc}
     */
    public function getCacheMaxAge(): int
    {
        return 10 * MINUTE_IN_SECONDS; // peut changer à chaque enregistrement de notice (candidat descripteurs, etc.)
    }

    /**
     * Génère une exception si le nom de l'index a interroger n'a pas été indiqué dans le paramètre source.
     *
     * @param string $source Paramètre source à tester.
     *
     * @throws InvalidArgumentException
     */
    private function checkSource(string $source): void
    {
        if (empty($source)) {
            throw new InvalidArgumentException('source is required');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultSuggestions(string $source = ''): array
    {
        $this->checkSource($source);

        // Pour trier par nom il faut utiliser '_key' à partir de ES 6.0 (avant c'était '_term')
        // cf https://www.elastic.co/guide/en/elasticsearch/reference/6.0/
        // search-aggregations-bucket-terms-aggregation.html#search-aggregations-bucket-terms-aggregation-order
        $sort = version_compare($this->elasticsearchVersion, '6.0.0', '>=') ? '_key' : '_term';

        // https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations-bucket-terms-aggregation.html
        $query = [
            'size' => 0,
            'aggs' => [
                'lookup' => [
                    'terms' => [
                        'field' => "filter.$source",
                        'size' => 100,
                        'order' => [$sort => 'asc'],
                    ],
                ],
            ],
        ];

        // Exécute la requête
        $result = docalist('elasticsearch')->post('/{index}/_search', $query);
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

    /**
     * {@inheritDoc}
     */
    public function getSuggestions(string $search, string $source = ''): array
    {
        $this->checkSource($source);

        // Important : le code ci-dessous ne fonctionne que pour ES > 6.1 (introduction de "skip_duplicates")
        // Sinon, il faut utiliser ES 2.x (cf. le code qu'on avait précédemment)
        // Il n'y a pas de solution pour ES 5...

        // Il faudra réimplémenter les index lookup autrement, sans passer par le completion suggester qui n'est plus
        // adapté.
        // Solution envisagée : facette de type terms sur les keywords en ne conservant que ce qui commence par le
        // texte recherché (via la propriété include).
        // Problème : il faut être insensible à la casse, aux accents, aux caractères spéciaux, etc.
        // On peut tenter avec des regexp (si on cherche "a", on recherche [aAÀÁÂÃÄÅàáâãäåĀāĂăĄą])
        // Ou alors, lors de l'indexation, on tokenize chaque expression et on la stocke sous la forme "tokens=riche".
        // Lors de la recherche, c'est plus simple (pas d'accents à gérer) et on retourne la forme riche.

        // @see https://www.elastic.co/guide/en/elasticsearch/reference/master/search-suggesters-completion.html
        $query = [
            'size' => 0,
            '_source' => false,
            'suggest' => [
                'lookup' => [
                    'text' => $search,
                    'completion' => [
                        'field' => "suggest.$source",
                        'size' => 100,
                        'skip_duplicates' => true,
                        // 'fuzzy' => true
                        // 'prefix_len' => 1,
                    ],
                ],
            ],
        ];

        // Exécute la requête
        $result = docalist('elasticsearch')->post('/{index}/_search?filter_path=suggest.lookup.options.text', $query);

        // Récupère les suggestions
        if (! isset($result->suggest->lookup[0]->options)) {
            return ['not set'];
        }

        // Ok. Résultat de la forme suivante : [{"text":"artwork","score":1},{"text":"artistic","score":1}]
        return $result->suggest->lookup[0]->options;
    }

    /**
     * {@inheritDoc}
     */
    public function convertCodes(array $data, string $source = ''): array
    {
        return array_combine($data, $data); // Lookup sur index : label = code
    }
}
