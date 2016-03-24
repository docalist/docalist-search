<?php
/**
 * This file is part of the "Docalist Biblio UserData" plugin.
 *
 * Copyright (C) 2015-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Tests\Biblio\UserData
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Tests\Search\QueryDSL;

use WP_UnitTestCase;
use Docalist\Search\QueryDSL\Version200 as DSL;

class Version200Test extends WP_UnitTestCase
{
    public function assertValidQuery(array $query)
    {
        $debug = false;

        $query = ['query' => $query];

        if ($debug) {
            $json = json_encode($query, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            echo "\n------------------------------------------------------------------------------\n$json\n";
        }

        $response = docalist('elastic-search')->get('/_validate/query?explain&rewrite=true', $query);
        if ($debug) {
            $json = json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            echo $json, "\n";
        }

        if (! isset($response->explanations)) {
            return $this->fail('Réponse ES non gérée');
        }
        // on considère que la query est valide si elle est acceptée par au moins un des index
        $error = '';
        foreach($response->explanations as $explanation) {
            if ($explanation->valid) {
                return $this;
            }
            $error = $explanation->error;
        }

        $json = json_encode($query, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $this->fail("Generated request is not valid: \n$json\n$error");
    }

    public function testMatchAll()
    {
        $dsl = new DSL();

        $query = $dsl->matchAll();
        $this->assertSame($query, [ 'match_all' => [] ]);
        $this->assertValidQuery($query);

        $args = ['boost' => 1.2, /* '_name' => 'test_query' */];
        $query = $dsl->matchAll($args);
        $this->assertSame($query, [ 'match_all' => $args ]);
        $this->assertValidQuery($query);
    }

    public function testMatchNone()
    {
        $dsl = new DSL();

        $query = $dsl->matchNone();
        $this->assertSame($query, [ 'term' => ['-' => ''] ]);
        $this->assertValidQuery($query);
    }

    public function testMatch()
    {
        $dsl = new DSL();

        // Paramètres par défaut
        $query = $dsl->match('bonjour le monde');
        $this->assertSame($query, ['match' => ['_all' => 'bonjour le monde']]);
        $this->assertValidQuery($query);

        // type par défaut
        $query = $dsl->match('bonjour le monde', 'title');
        $this->assertSame($query, ['match' => ['title' => 'bonjour le monde']]);
        $this->assertValidQuery($query);

        // match
        $query = $dsl->match('bonjour le monde', 'title', 'match');
        $this->assertSame($query, ['match' => ['title' => 'bonjour le monde']]);
        $this->assertValidQuery($query);

        // match + operator and
        $query = $dsl->match('bonjour le monde', 'title', 'match', ['operator' => 'and']);
        $this->assertSame($query, ['match' => ['title' => ['query' => 'bonjour le monde', 'operator' => 'and']]]);
        $this->assertValidQuery($query);

        // match_phrase
        $query = $dsl->match('bonjour le monde', 'title', 'match_phrase');
        $this->assertSame($query, ['match_phrase' => ['title' => 'bonjour le monde']]);
        $this->assertValidQuery($query);

        // match_phrase_prefix
        $query = $dsl->match('bonjour le monde', 'title', 'match_phrase_prefix');
        $this->assertSame($query, ['match_phrase_prefix' => ['title' => 'bonjour le monde']]);
        $this->assertValidQuery($query);

        // tous les arguments autorisés
        $args = [
            'operator' => 'and', 'analyzer' => 'fr-text', 'slop' => 2, 'minimum_should_match' => 2, 'fuzziness' => 2,
            'fuzzy_rewrite' => 'constant_score', 'prefix_length' => 2, 'fuzzy_transpositions' =>10,
            'max_expansions' => 100, 'lenient' => true, 'cutoff_frequency' => 0.01, 'zero_terms_query' => 'none',
            '_name' => 'test_query', 'boost' => 1.5,
        ];
        $query = $dsl->match('bonjour le monde', 'title', 'match', $args);
        $this->assertSame($query, ['match' => ['title' => ['query' => 'bonjour le monde'] + $args]]);
        $this->assertValidQuery($query);
    }

    /**
     * Teste match() avec des paramètres invalides.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid match parameters: hello, world
     */
    public function testMatchBadParam()
    {
        $dsl = new DSL();

        $dsl->match('title', 'bonjour', 'match', ['hello' => 1, 'world' => 2]);
    }

    public function testMultiMatch()
    {
        $dsl = new DSL();

        // Paramètres par défaut
        $query = $dsl->multiMatch('bonjour le monde');
        $this->assertSame($query, ['multi_match' => [
            'type' => 'best_fields',
            'query' => 'bonjour le monde',
            'fields' => ['_all'],
        ]]);
        $this->assertValidQuery($query);

        // Champs passés sous forme de tableau
        $query = $dsl->multiMatch('bonjour le monde', ['title', 'content']);
        $this->assertSame($query, ['multi_match' => [
            'type' => 'best_fields',
            'query' => 'bonjour le monde',
            'fields' => ['title', 'content'],
        ]]);
        $this->assertValidQuery($query);

        // Champs passés sous forme de chaine
        $query = $dsl->multiMatch('bonjour le monde', '  title   , content   ');
        $this->assertSame($query, ['multi_match' => [
            'type' => 'best_fields',
            'query' => 'bonjour le monde',
            'fields' => ['title', 'content'],
        ]]);
        $this->assertValidQuery($query);

        // best_fields
        foreach(['best_fields', 'most_fields', 'cross_fields', 'phrase', 'phrase_prefix'] as $type) {
            $query = $dsl->multiMatch('bonjour le monde', 'title,content', $type);
            $this->assertSame($query, ['multi_match' => [
                'type' => $type,
                'query' => 'bonjour le monde',
                'fields' => ['title', 'content'],
            ]]);
            $this->assertValidQuery($query);
        }

        // tous les arguments autorisés
        $args = [
            //'query', 'fields', 'type', // fixés par nous donc pas autorisés dans $parameters
            'analyzer' => 'fr-text', 'cutoff_frequency' => 0.01, 'fuzziness' => 2, 'fuzzy_rewrite' => 'constant_score',
            'use_dis_max' =>true, 'lenient' => true, 'max_expansions' => 100, 'minimum_should_match' => 2,
            'operator' => 'and', 'prefix_length' => 2, 'slop' => 2, 'tie_breaker' => 0.3, 'zero_terms_query' => 'none',
            'boost' => 1.5, '_name' =>'test_query',
        ];

        $query = $dsl->multiMatch('bonjour le monde', ['title', 'content'], 'cross_fields', $args);
        $this->assertSame($query, ['multi_match' => [
            'type' => 'cross_fields',
            'query' => 'bonjour le monde',
            'fields' => ['title', 'content'],
        ] + $args]);
    }

    /**
     * Teste multiMatch() avec des paramètres invalides.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid MultiMatch parameters: hello, world
     */
    public function testMultiMatchBadParam()
    {
        $dsl = new DSL();

        $dsl->multiMatch('bonjour', 'title', 'best_fields', ['hello' => 1, 'world' => 2]);
    }

    public function testQueryString()
    {
        $dsl = new DSL();

        // Paramètres par défaut
        $query = $dsl->queryString('bonj* +monde');
        $this->assertSame($query, ['query_string' => [
            'query' => 'bonj* +monde',
            'default_field' => '_all',
        ]]);
        $this->assertValidQuery($query);

        // Champs
        $query = $dsl->queryString('bonj* +monde', 'title');
        $this->assertSame($query, ['query_string' => [
            'query' => 'bonj* +monde',
            'default_field' => 'title',
        ]]);
        $this->assertValidQuery($query);

        $query = $dsl->queryString('bonj* +monde', 'title,content');
        $this->assertSame($query, ['query_string' => [
            'query' => 'bonj* +monde',
            'fields' => ['title', 'content'],
        ]]);
        $this->assertValidQuery($query);

        // tous les arguments autorisés
        $args = [
            // 'query', 'fields', 'default_field', 'default_operator', // fixés par nous, pas autorisés dans $parameters
            'analyzer' => 'fr-text', 'quote_analyzer' => 'fr-text', 'allow_leading_wildcard' => false,
            'auto_generate_phrase_queries' => true, 'auto_generated_phrase_queries' => true, // avec un "d" dans ES 5.0 alpha ?
            'max_determined_states' => 100, 'lowercase_expanded_terms' => true, 'enable_position_increment' => true,
            'escape' => '$',
            'use_dis_max' => true, 'fuzzy_prefix_length' => 3, 'fuzzy_max_expansions' => 10,
            'fuzzy_rewrite' => 'constant_score', 'phrase_slop' => 2,
            'fuzziness' => 2, 'tie_breaker' => 0.3, 'analyze_wildcard' => true, 'rewrite' => 'constant_score',
            'minimum_should_match' => 2, 'quote_field_suffix' => 'a',
            'lenient' => true, 'locale' => 'fr_FR', 'time_zone' => 'Europe/Paris',
            'boost' => 1.2, '_name' => 'test_query',
        ];

        $query = $dsl->queryString('bonj* +monde', 'title,content', 'and', $args);
        $this->assertSame($query, ['query_string' => [
            'query' => 'bonj* +monde',
            'fields' => ['title', 'content'],
            'default_operator' => 'and',
        ] + $args]);
    }

    public function testSimpleQueryString()
    {
        $dsl = new DSL();

        // Paramètres par défaut
        $query = $dsl->simpleQueryString('bonj* +monde');
        $this->assertSame($query, ['simple_query_string' => [
            'query' => 'bonj* +monde',
            'fields' => ['_all'],
        ]]);
        $this->assertValidQuery($query);

        // Champs
        $query = $dsl->simpleQueryString('bonj* +monde', 'title');
        $this->assertSame($query, ['simple_query_string' => [
            'query' => 'bonj* +monde',
            'fields' => ['title'],
        ]]);
        $this->assertValidQuery($query);

        $query = $dsl->simpleQueryString('bonj* +monde', 'title,content');
        $this->assertSame($query, ['simple_query_string' => [
            'query' => 'bonj* +monde',
            'fields' => ['title', 'content'],
        ]]);
        $this->assertValidQuery($query);

        // tous les arguments autorisés
        $args = [
            // 'query', 'fields', 'default_operator', // fixés par nous, pas autorisés dans $parameters
            'analyzer' => 'fr-text', 'minimum_should_match' => 2, 'flags' => 'OR|AND|PREFIX', 'locale' => 'ROOT',
            'lowercase_expanded_terms' => true, 'lenient' => true, 'analyze_wildcard' => true, 'boost' => 1.2,
            '_name' => 'test_query',
        ];

        $query = $dsl->simpleQueryString('bonj* +monde', 'title,content', 'and', $args);
        $this->assertSame($query, ['simple_query_string' => [
            'query' => 'bonj* +monde',
            'fields' => ['title', 'content'],
            'default_operator' => 'and',
        ] + $args]);
    }

    public function testTerm()
    {
        $dsl = new DSL();

        // Paramètres par défaut
        $query = $dsl->term('bonjour');
        $this->assertSame($query, [ 'term' => ['_all' => 'bonjour'] ]);
        $this->assertValidQuery($query);

        // Si on passe un seul terme, ça génère une TermQuery
        $query = $dsl->term('publish', 'status');
        $this->assertSame($query, [ 'term' => ['status' => 'publish'] ]);
        $this->assertValidQuery($query);

        // Si on passe un tableau de termes, ça génère une TermsQuery (avec un "S")
        $query = $dsl->term(['publish', 'pending'], 'status');
        $this->assertSame($query, [ 'terms' => ['status' => ['publish', 'pending']] ]);
        $this->assertValidQuery($query);

        // Mais si le tableau ne contient qu'un seul terme, ça génère une TermQuery
        $query = $dsl->term(['publish'], 'status');
        $this->assertSame($query, [ 'term' => ['status' => 'publish'] ]);
        $this->assertValidQuery($query);

        // Si le tableau est vide, ça génère une TermsQuery vide
        $query = $dsl->term([], 'status');
        $this->assertSame($query, [ 'terms' => ['status' => []] ]);
        $this->assertValidQuery($query);

        // tous les arguments autorisés
        $args = [
            'boost' => 1.2, '_name' => 'test_query',
        ];

        $query = $dsl->term('publish', 'status', $args);
        $this->assertSame($query, ['term' => ['status' => ['value' => 'publish'] + $args]]);
    }


    public function testTerms()
    {
        $dsl = new DSL();

        // Paramètres par défaut
        $query = $dsl->terms('bonjour');
        $this->assertSame($query, [ 'term' => ['_all' => 'bonjour'] ]);
        $this->assertValidQuery($query);

        // Si on passe un seul terme, ça génère une TermQuery
        $query = $dsl->terms('publish', 'status');
        $this->assertSame($query, [ 'term' => ['status' => 'publish'] ]);
        $this->assertValidQuery($query);

        // Si on passe un tableau de termes, ça génère une TermsQuery (avec un "S")
        $query = $dsl->terms(['publish', 'pending'], 'status');
        $this->assertSame($query, [ 'terms' => ['status' => ['publish', 'pending']] ]);
        $this->assertValidQuery($query);

        // Mais si le tableau ne contient qu'un seul terme, ça génère une TermQuery
        $query = $dsl->terms(['publish'], 'status');
        $this->assertSame($query, [ 'term' => ['status' => 'publish'] ]);
        $this->assertValidQuery($query);

        // Si le tableau est vide, ça génère une TermsQuery vide
        $query = $dsl->terms([], 'status');
        $this->assertSame($query, [ 'terms' => ['status' => []] ]);
        $this->assertValidQuery($query);

        // tous les arguments autorisés
        $args = [
            'boost' => 1.2, '_name' => 'test_query',
            'index' => 'wp_prisme', 'type' => 'post', 'id' => 1, 'routing' => 'p', 'path' => 'comments',
        ];

        $query = $dsl->terms(null, 'status', $args);
        $this->assertSame($query, ['terms' => ['status' => ['value' => null] + $args]]);
    }

    public function testRange()
    {
        $dsl = new DSL();

        $range = ['gte' => 'a'];
        $query = $dsl->range($range);
        $this->assertSame($query, [ 'range' => ['_all' => $range] ]);
        $this->assertValidQuery($query);

        $range = ['gte' => 10];
        $query = $dsl->range($range, 'ref');
        $this->assertSame($query, [ 'range' => ['ref' => $range] ]);
        $this->assertValidQuery($query);

        $range = ['gte' => 1, 'gt' => 2, 'lte' => 3, 'lt' => 4];
        $query = $dsl->range($range, 'ref');
        $this->assertSame($query, [ 'range' => ['ref' => $range] ]);
        $this->assertValidQuery($query);

        $range = [
            'gte' => 1,
            'gt' => 2,
            'lte' => 3,
            'lt' => 4,
        ];
        $args = [
            'boost' => 0.1,
            'time_zone' => '+01:00',
            'format' => 'dd/MM/yyyy||yyyy'
        ];
        $query = $dsl->range($range, 'ref', $args);
        $this->assertSame($query, [ 'range' => ['ref' => $range + $args] ]);
        $this->assertValidQuery($query);
    }

    /**
     * Teste range() avec un tableau vide.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid range (empty)
     */
    public function testRangeEmptyRange()
    {
        $dsl = new DSL();

        $dsl->range([]);
    }

    /**
     * Teste range() avec un tableau vide.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid range operators: a, b
     */
    public function testRangeBadParam()
    {
        $dsl = new DSL();

        $dsl->range(['a' => 1, 'b' => 2]);
    }

    public function testExists()
    {
        $dsl = new DSL();

        $query = $dsl->exists('excerpt');
        $this->assertSame($query, [ 'exists' => ['field' => 'excerpt'] ]);
        $this->assertValidQuery($query);

        $query = $dsl->exists('excerpt', [/*'boost' => 1.5, */ '_name' => 'test_query']); // boost : >= 5.0
        $this->assertSame($query, [ 'exists' => ['field' => 'excerpt', /* 'boost' => 1.5, */ '_name' => 'test_query'] ]);
        $this->assertValidQuery($query);
    }

    public function testMissing()
    {
        $dsl = new DSL();

        $query = $dsl->missing('excerpt');
        $this->assertSame($query, [ 'bool' => ['must_not' => [ ['exists' => ['field' => 'excerpt']]] ] ]);
        $this->assertValidQuery($query);
    }

    public function testPrefix()
    {
        $dsl = new DSL();

        $query = $dsl->prefix('po');
        $this->assertSame($query, [ 'prefix' => ['_all' => 'po'] ]);
        $this->assertValidQuery($query);

        $query = $dsl->prefix('po', 'type');
        $this->assertSame($query, [ 'prefix' => ['type' => 'po'] ]);
        $this->assertValidQuery($query);

        $args = ['boost' => 1.5, '_name' => 'tt', 'rewrite' => 'constant_score'];
        $query = $dsl->prefix('po', 'type', $args);
        $this->assertSame($query, [ 'prefix' => ['type' => ['prefix' => 'po'] + $args ]]);
        $this->assertValidQuery($query);
    }

    public function testWildcard()
    {
        $dsl = new DSL();

        $query = $dsl->wildcard('p?s*');
        $this->assertSame($query, [ 'wildcard' => ['_all' => 'p?s*'] ]);
        $this->assertValidQuery($query);

        $query = $dsl->wildcard('p?s*', 'type');
        $this->assertSame($query, [ 'wildcard' => ['type' => 'p?s*'] ]);
        $this->assertValidQuery($query);

        $args = ['boost' => 1.5, '_name' => 'tt', 'rewrite' => 'constant_score'];
        $query = $dsl->wildcard('p?s*', 'type', $args);
        $this->assertSame($query, [ 'wildcard' => ['type' => ['wildcard' => 'p?s*'] + $args]]);
        $this->assertValidQuery($query);
    }

    public function testType()
    {
        $dsl = new DSL();

        $query = $dsl->type('post');
        $this->assertSame($query, [ 'type' => ['value' => 'post'] ]);
        $this->assertValidQuery($query);

        $args = [/*'boost' => 1.5, */'_name' => 'tt']; // boost : ES >= 5
        $query = $dsl->type('post', $args);
        $this->assertSame($query, [ 'type' => ['value' => 'post'] + $args ]);
        $this->assertValidQuery($query);
    }

    public function testIds()
    {
        $dsl = new DSL();

        $query = $dsl->ids(2202874);
        $this->assertSame($query, [ 'ids' => ['values' => [2202874]] ]);
        $this->assertValidQuery($query);

        $query = $dsl->ids(2202874, 'post');
        $this->assertSame($query, [ 'ids' => ['values' => [2202874], 'type' => 'post'] ]);
        $this->assertValidQuery($query);

        $query = $dsl->ids([2202874, 2202877, 2578998]);
        $this->assertSame($query, [ 'ids' => ['values' => [2202874, 2202877, 2578998]] ]);
        $this->assertValidQuery($query);

        $query = $dsl->ids([2202874, 2202877, 2578998], ['post', 'page']);
        $this->assertSame($query, [ 'ids' => ['values' => [2202874, 2202877, 2578998], 'type' => ['post', 'page']] ]);
        $this->assertValidQuery($query);

        $args = ['boost' => 1.5, '_name' => 'tt']; // boost : ES >= 5
        $query = $dsl->ids(2202874, 'post', $args);
        $this->assertSame($query, [ 'ids' => ['values' => [2202874], 'type' => 'post'] + $args ]);
        $this->assertValidQuery($query);
    }

    public function testBool()
    {
        $dsl = new DSL();

        $query = $dsl->bool([['must' => $dsl->term('post', 'type')]]);
        $this->assertSame($query, [ 'bool' => ['must' => [$dsl->term('post', 'type')]] ]);
        $this->assertValidQuery($query);

        $query = $dsl->bool([
            $dsl->must($dsl->term('un', 'must')),
            $dsl->must($dsl->term('deux', 'must')),

            $dsl->filter($dsl->term('un', 'filter')),
            $dsl->filter($dsl->term('deux', 'filter')),

            $dsl->should($dsl->term('un', 'should')),
            $dsl->should($dsl->term('deux', 'should')),

            $dsl->mustNot($dsl->term('un', 'mustnot')),
            $dsl->mustNot($dsl->term('deux', 'mustnot')),
        ]);
        $this->assertSame($query, [ 'bool' => [
            'must'      => [ ['term' => ['must'     => 'un']], ['term' => ['must'   => 'deux']] ],
            'filter'    => [ ['term' => ['filter'   => 'un']], ['term' => ['filter' => 'deux']] ],
            'should'    => [ ['term' => ['should'   => 'un']], ['term' => ['should' => 'deux']] ],
            'must_not'  => [ ['term' => ['mustnot'  => 'un']], ['term' => ['mustnot'=> 'deux']] ],
        ] ]);
        $this->assertValidQuery($query);


        $args = [
            'disable_coord' => true, 'minimum_should_match' => 2, 'minimum_number_should_match' => 2,
            'adjust_pure_negative' => true, 'boost' => 1.3, '_name' => 'test',
        ];
        $query = $dsl->bool([['must' => $dsl->term('post', 'type')]], $args);
        $this->assertSame($query, [ 'bool' => ['must' => [$dsl->term('post', 'type')]] + $args ]);
        $this->assertValidQuery($query);
    }
}
