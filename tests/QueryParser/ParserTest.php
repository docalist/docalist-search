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

namespace Docalist\Tests\Search\QueryParser;

use WP_UnitTestCase;
use Docalist\Search\QueryParser\Parser;
use Docalist\Search\QueryDSL;

/**
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ParserTest extends WP_UnitTestCase
{
    /**
     * Vérifie que la requête passée en paramètre génère la query attendue.
     *
     * @dataProvider queriesProvider
     */
    public function testParse($string, $query)
    {
        $parser = new Parser();
        $this->assertSame($query, $parser->parse($string));
    }

    /**
     * Requêtes de test pour testParser().
     *
     * @return array
     */
    public function queriesProvider()
    {
        $dsl = docalist('elasticsearch-query-dsl'); /* @var QueryDSL $dsl */
        return [

            ['', null],
            ['terme', $dsl->match('_all', 'terme')],
            ['hello world!', $dsl->match('_all', 'hello world')],
            ['title:hello title:world!', $dsl->bool([
                $dsl->must($dsl->match('title', 'hello')),
                $dsl->must($dsl->match('title', 'world')),
            ])],
            ['title:(hello world!)', $dsl->match('title', 'hello world')],
            ['title:hello world!', $dsl->match('title', 'hello world')], // différent de lucene
            ['title:hello world! status:publish', $dsl->bool([
                $dsl->must($dsl->match('title', 'hello world')),
                $dsl->must($dsl->match('status', 'publish')),
            ])],

            // Phrases
            ['"hello"', $dsl->match('_all', 'hello', 'match_phrase')],
            ['"hello world!"', $dsl->match('_all', 'hello world', 'match_phrase')],
            ['title:"hello world!"', $dsl->match('title', 'hello world', 'match_phrase')],
            ['title:"hello world!" author:"Daniel Ménard"', $dsl->bool([
                $dsl->must($dsl->match('title', 'hello world', 'match_phrase')),
                $dsl->must($dsl->match('author', 'Daniel Ménard', 'match_phrase')),
            ])],

            // Préfixe
            ['hel*', $dsl->prefix('_all', 'hel')],
            ['status:pub*', $dsl->prefix('status', 'pub')],

            // Love
            ['+hello +world!', $dsl->bool([
                $dsl->must($dsl->match('_all', 'hello')),
                $dsl->must($dsl->match('_all', 'world')),
            ])],
            ['(+hello +world!)', $dsl->bool([
                $dsl->must($dsl->match('_all', 'hello')),
                $dsl->must($dsl->match('_all', 'world')),
            ])],
            ['+(hello world!)', $dsl->match('_all', 'hello world')],
            ['+(hello +world!)', $dsl->bool([
                $dsl->must($dsl->match('_all', 'hello')),
                $dsl->must($dsl->match('_all', 'world')),
            ])],
            ['+(hello +(world!))', $dsl->bool([
                $dsl->must($dsl->match('_all', 'hello')),
                $dsl->must($dsl->match('_all', 'world')),
            ])],

            // Hate
            ['hello -world!', $dsl->bool([
                $dsl->must($dsl->match('_all', 'hello')),
                $dsl->mustNot($dsl->match('_all', 'world')),
            ])],

            ['-hello -world!', $dsl->bool([
                $dsl->mustNot($dsl->match('_all', 'hello')),
                $dsl->mustNot($dsl->match('_all', 'world')),
            ])],

            ['-(hello world!)', $dsl->bool([
                $dsl->mustNot($dsl->match('_all', 'hello world')),
            ])],

            ['-title:(hello world!)', $dsl->bool([
                $dsl->mustNot($dsl->match('title', 'hello world')),
            ])],
            ['title:(hello world!) -status:pending', $dsl->bool([
                $dsl->must($dsl->match('title', 'hello world')),
                $dsl->mustNot($dsl->match('status', 'pending')),
            ])],

            ['-"hello world!"', $dsl->bool([
                $dsl->mustNot($dsl->match('_all', 'hello world', 'match_phrase')),
            ])],

            ['-title:"hello world!"', $dsl->bool([
                $dsl->mustNot($dsl->match('title', 'hello world', 'match_phrase')),
            ])],


            ['-(hello -world!)', $dsl->bool([
                $dsl->mustNot(
                    $dsl->bool([
                        $dsl->must($dsl->match('_all', 'hello')),
                        $dsl->mustNot($dsl->match('_all', 'world'))
                    ])
                )
            ])],

            ['-(hello -(world!))', $dsl->bool([
                $dsl->mustNot(
                    $dsl->bool([
                        $dsl->must($dsl->match('_all', 'hello')),
                        $dsl->mustNot($dsl->match('_all', 'world'))
                    ])
                )
            ])],

            // AND / OR / NOT
            ['hello AND world!', $dsl->bool([
                $dsl->must($dsl->match('_all', 'hello')),
                $dsl->must($dsl->match('_all', 'world')),
            ])],

            ['hello OR world!', $dsl->bool([
                $dsl->should($dsl->match('_all', 'hello')),
                $dsl->should($dsl->match('_all', 'world')),
            ])],

            ['bonjour OR hello AND world!', $dsl->bool([
                $dsl->should($dsl->match('_all', 'bonjour')),
                $dsl->should(
                    $dsl->bool([
                        $dsl->must($dsl->match('_all', 'hello')),
                        $dsl->must($dsl->match('_all', 'world')),
                    ])
                )
            ])],

            ['bonjour OR (hello AND world!)', $dsl->bool([
                $dsl->should($dsl->match('_all', 'bonjour')),
                $dsl->should(
                    $dsl->bool([
                        $dsl->must($dsl->match('_all', 'hello')),
                        $dsl->must($dsl->match('_all', 'world')),
                    ])
                )
            ])],

            ['hello AND world OR bonjour!', $dsl->bool([
                $dsl->should(
                    $dsl->bool([
                        $dsl->must($dsl->match('_all', 'hello')),
                        $dsl->must($dsl->match('_all', 'world')),
                    ])
                ),
                $dsl->should($dsl->match('_all', 'bonjour'))
            ])],

            ['vacation london OR paris', $dsl->bool([ // différent de google (OR prioritaire sur AND)
                $dsl->should($dsl->match('_all', 'vacation london')),
                $dsl->should($dsl->match('_all', 'paris'))
            ])],

            ['(a) OR b', $dsl->bool([
                $dsl->should($dsl->match('_all', 'a')),
                $dsl->should($dsl->match('_all', 'b'))
            ])],

//            ['(a) OR b', null],
//             ['(vacation london) OR paris', $dsl->bool([ // fails
//                 $dsl->should($dsl->match('_all', 'vacation london')),
//                 $dsl->should($dsl->match('_all', 'paris'))
//             ])],
/*
            ['(hello AND world) OR bonjour!', $dsl->bool([ // fails
                $dsl->should(
                    $dsl->bool([
                        $dsl->must($dsl->match('_all', 'hello')),
                        $dsl->must($dsl->match('_all', 'world')),
                    ])
                ),
                $dsl->should($dsl->match('_all', 'bonjour'))
            ])],
*/

//             ['hello NOT world!', $dsl->bool([ // fails
//                 $dsl->should($dsl->match('_all', 'hello')),
//                 $dsl->must($dsl->match('_all', 'world')),
//             ])],

        ];
    }

}
