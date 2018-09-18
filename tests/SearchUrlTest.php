<?php
/**
 * This file is part of the "Docalist Biblio UserData" plugin.
 *
 * Copyright (C) 2016-2017 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Tests\Biblio\UserData
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search\Tests;

use WP_UnitTestCase;
use Docalist\Search\SearchUrl;
use Docalist\Search\QueryDSL;
use Docalist\Search\SearchRequest;

class SearchUrlTest extends WP_UnitTestCase
{
    /**
     * Vérifie que l'url passée en paramètre est correctement analysée.
     *
     * @dataProvider urlProvider
     */
    public function testConstruct($url, $parameters, $cleanUrl)
    {
        $searchUrl = new SearchUrl($url);
        $this->assertSame($url, $searchUrl->getUrl());
        $this->assertSame($parameters, $searchUrl->getParameters());
        $this->assertSame($cleanUrl, $searchUrl->getCleanUrl());
    }

    /**
     * Urls de test pour testConstruct().
     *
     * @return array
     */
    public function urlProvider()
    {
        return [
            // QueryString vide
            [
                'http://example.org/',
                [],
                'http://example.org/',
            ],

            [
                'http://example.org/?',
                [],
                'http://example.org/',
            ],

            // Paramètre simple
            [
                'http://example.org/?status=publish',
                ['status' => 'publish'],
                'http://example.org/?status=publish',
            ],

            [
                'http://example.org/?topic.filter=social',
                ['topic.filter' => 'social'],
                'http://example.org/?topic.filter=social',
            ],

            [
                'http://example.org/?topic.bad_topic.filter=social',
                ['topic.bad.topic.filter' => 'social'], // le "_" a été perdu
                'http://example.org/?topic.bad.topic.filter=social',
            ],

            // Les paramètres vides sont ignorés (exemple : recherche avancée)
            [
                'http://example.org/?status=publish&title=&author&date=',
                ['status' => 'publish'],
                'http://example.org/?status=publish',
            ],

            // Paramètre multivalué - tableau sans clés
            [
                'http://example.org/?status[]=publish&status[]=pending',
                ['status' => ['publish', 'pending']],
                'http://example.org/?status=publish,pending',
            ],

            [
                'http://example.org/?status%5B%5D=publish&status%5B%5D=pending',
                ['status' => ['publish', 'pending']],
                'http://example.org/?status=publish,pending',
            ],

            // Paramètre multivalué - tableau avec clés
            [
                'http://example.org/?status[0]=publish&status[1]=pending',
                ['status' => ['publish', 'pending']],
                'http://example.org/?status=publish,pending',
            ],

            [
                'http://example.org/?status%5B0%5D=publish&status%5B1%5D=pending',
                ['status' => ['publish', 'pending']],
                'http://example.org/?status=publish,pending',
            ],

            // Paramètre multivalué - valeurs séparées par une virgule
            [
                'http://example.org/?status=publish,pending',
                ['status' => ['publish', 'pending']],
                'http://example.org/?status=publish,pending',
            ],
            [
                'http://example.org/?status=publish%2Cpending',
                ['status' => ['publish', 'pending']],
                'http://example.org/?status=publish,pending',
            ],

            // Numéro de page
            [
                'http://example.org/?status=publish&page', // pas de valeur, ignoré
                ['status' => 'publish'],
                'http://example.org/?status=publish',
            ],
            [
                'http://example.org/?status=publish&page=0', // zéro n'est pas valide, ignoré
                ['status' => 'publish'],
                'http://example.org/?status=publish',
            ],
            [
                'http://example.org/?status=publish&page=1', // c'est la page par défaut, ignoré
                ['status' => 'publish'],
                'http://example.org/?status=publish',
            ],
            [
                'http://example.org/?status=publish&page=-1', // ce n'est pas valide, ignoré
                ['status' => 'publish'],
                'http://example.org/?status=publish',
            ],
            [
                'http://example.org/?status=publish&page=2', // ok, numéro de page valide
                ['status' => 'publish', 'page' => 2],
                'http://example.org/?status=publish&page=2',
            ],


            // Nombre de notices par page
            [
                'http://example.org/?status=publish&size', // pas de valeur, ignoré
                ['status' => 'publish'],
                'http://example.org/?status=publish',
            ],
            [
                'http://example.org/?status=publish&size=-1', // ce n'est pas valide, ignoré
                ['status' => 'publish'],
                'http://example.org/?status=publish',
            ],
            [
                'http://example.org/?status=publish&size=10', // c'est la valeur par défaut, ignoré
                ['status' => 'publish'],
                'http://example.org/?status=publish',
            ],
            [
                'http://example.org/?status=publish&size=12', // ok, taille valide
                ['status' => 'publish', 'size' => 12],
                'http://example.org/?status=publish&size=12',
            ],

            // Vérifie que seuls les filtres sont découpés à la virgule (pas un titre, par exemple)
            [
                'http://example.org/?status=publish,pending&title=bon,jour',
                ['status' => ['publish', 'pending'], 'title' => 'bon,jour'],
                'http://example.org/?status=publish,pending&title=bon%2Cjour', // la 2nde vigule a été encodée
            ],

        ];
    }

    public function testGetBaseUrl()
    {
        $url = 'http://example.org/';

        $searchUrl = new SearchUrl($url);
        $this->assertSame($url, $searchUrl->getBaseUrl());

        $searchUrl = new SearchUrl("$url?");
        $this->assertSame($url, $searchUrl->getBaseUrl());

        $searchUrl = new SearchUrl("$url?a=b");
        $this->assertSame($url, $searchUrl->getBaseUrl());

        $searchUrl = new SearchUrl("$url?a=?");
        $this->assertSame($url, $searchUrl->getBaseUrl());
    }

    /**
     * Teste la méthode toggleFilter()
     */
    public function testToggleFilter()
    {
        $url = 'http://example.org/?in=event&status=pending,draft&q=&topic.filter%5B%5D=Histoire';

        $searchUrl = new SearchUrl($url);

        // sanity check : vérifie qu'on a bien la bonne url propre
        $this->assertSame(
            'http://example.org/?in=event&status=pending,draft&topic.filter=Histoire',
            $searchUrl->getCleanUrl()
        );

        // Supprime le filtre 'in'
        $this->assertSame(
            'http://example.org/?status=pending,draft&topic.filter=Histoire',
            $searchUrl->toggleFilter('in')
        );

        // Supprime le filtre 'in:event'
        $this->assertSame(
            'http://example.org/?status=pending,draft&topic.filter=Histoire',
            $searchUrl->toggleFilter('in', 'event')
        );

        // Supprime le filtre 'status'
        $this->assertSame(
            'http://example.org/?in=event&topic.filter=Histoire',
            $searchUrl->toggleFilter('status')
        );

        // Supprime le filtre 'status:pending'
        $this->assertSame(
            'http://example.org/?in=event&status=draft&topic.filter=Histoire',
            $searchUrl->toggleFilter('status', 'pending')
        );

        // Supprime le filtre 'status:draft'
        $this->assertSame(
            'http://example.org/?in=event&status=pending&topic.filter=Histoire',
            $searchUrl->toggleFilter('status', 'draft')
        );

        // Supprime le filtre 'topic.filter:Histoire'
        $this->assertSame(
            'http://example.org/?in=event&status=pending,draft',
            $searchUrl->toggleFilter('topic.filter', 'Histoire')
        );

        // Ajoute le filtre 'in:work'
        $this->assertSame(
            'http://example.org/?in=event,work&status=pending,draft&topic.filter=Histoire',
            $searchUrl->toggleFilter('in', 'work')
        );

        // Ajoute le filtre 'status:publish'
        $this->assertSame(
            'http://example.org/?in=event&status=pending,draft,publish&topic.filter=Histoire',
            $searchUrl->toggleFilter('status', 'publish')
        );

        // Ajoute un nouveau filtre 'createdby:dmenard'
        $this->assertSame(
            'http://example.org/?in=event&status=pending,draft&topic.filter=Histoire&createdby=dmenard',
            $searchUrl->toggleFilter('createdby', 'dmenard')
        );

        // Ajoute un nouveau filtre 'topic.genre.filter:book'
        $this->assertSame(
            'http://example.org/?in=event&status=pending,draft&topic.filter=Histoire&topic.genre.filter=book',
            $searchUrl->toggleFilter('topic.genre.filter', 'book')
        );

    }

    /**
     * Teste la méthode getUrlForPage()
     *
     * @dataProvider pageProvider
     */
    public function testGetUrlForPage($url, $page, $result)
    {
        $searchUrl = new SearchUrl($url);
        $this->assertSame($result, $searchUrl->getUrlForPage($page));
    }

    public function pageProvider()
    {
        return [
            [
                'http://example.org/?in=event,work&status=pending,draft&topic.filter=Histoire',
                2,
                'http://example.org/?in=event,work&status=pending,draft&topic.filter=Histoire&page=2'
            ],

            [
                'http://example.org/?in=event,work&status=pending,draft&topic.filter=Histoire',
                1,
                'http://example.org/?in=event,work&status=pending,draft&topic.filter=Histoire'
            ],

            [
                'http://example.org/?in=event,work&status=pending,draft&topic.filter=Histoire&size=20',
                1,
                'http://example.org/?in=event,work&status=pending,draft&topic.filter=Histoire&size=20'
            ],

            [
                'http://example.org/?in=event,work&status=pending,draft&topic.filter=Histoire&size=20',
                3,
                'http://example.org/?in=event,work&status=pending,draft&topic.filter=Histoire&size=20&page=3'
            ],
        ];
    }

    /**
     * Vérifie qu'une exception est levée si on appelle getUrlForPage avec un numéro de page invalide.
     *
     * @dataProvider badPageProvider
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Incorrect page
     */
    public function testGetUrlForPageWithBadPage($page)
    {
        (new SearchUrl())->getUrlForPage($page);
    }

    public function badPageProvider()
    {
        return [
            [null],         // pas un entier
            [''],           // pas un entier
            ['a'],          // pas un entier
            [0],          // pas autorisé
        ];
    }

    /**
     * Teste la méthode getUrlForSize()
     *
     * @dataProvider sizeProvider
     */
    public function testGetUrlForSize($url, $size, $result)
    {
        $searchUrl = new SearchUrl($url);
        $this->assertSame($result, $searchUrl->getUrlForSize($size));
    }

    public function sizeProvider()
    {
        return [
            [
                'http://example.org/?in=event,work&status=pending,draft&topic.filter=Histoire',
                12,
                'http://example.org/?in=event,work&status=pending,draft&topic.filter=Histoire&size=12'
            ],

            [
                'http://example.org/?in=event,work&status=pending,draft&topic.filter=Histoire',
                10,
                'http://example.org/?in=event,work&status=pending,draft&topic.filter=Histoire'
            ],

            [
                'http://example.org/?in=event,work&status=pending,draft&topic.filter=Histoire&page=2',
                12,
                'http://example.org/?in=event,work&status=pending,draft&topic.filter=Histoire&size=12' // reste page
            ],

            [
                'http://example.org/?in=event,work&status=pending,draft&topic.filter=Histoire&page=2',
                10,
                'http://example.org/?in=event,work&status=pending,draft&topic.filter=Histoire' // reste page + default size
            ],
        ];
    }

    /**
     * Vérifie qu'une exception est levée si on appelle getUrlForSize avec une taille invalide.
     *
     * @dataProvider badSizeProvider
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Incorrect size
     */
    public function testGetUrlForPageWithBadSize($size)
    {
        (new SearchUrl())->getUrlForSize($size);
    }

    public function badSizeProvider()
    {
        return [
            [null],         // pas un entier
            [''],           // pas un entier
            ['a'],          // pas un entier
            [-1],           // pas autorisé
        ];
    }

    /**
     * Teste la méthode getUrlForSort()
     *
     * @dataProvider sortProvider
     */
    public function testGetUrlForSort($url, $sort, $result)
    {
        $searchUrl = new SearchUrl($url);
        $this->assertSame($result, $searchUrl->getUrlForSort($sort));
    }

    public function sortProvider()
    {
        return [
            [
                'http://example.org/?in=event,work&status=pending,draft&topic.filter=Histoire',
                '%',
                'http://example.org/?in=event,work&status=pending,draft&topic.filter=Histoire' // tri par défaut
            ],

            [
                'http://example.org/?in=event,work&status=pending,draft&topic.filter=Histoire',
                '_score',
                'http://example.org/?in=event,work&status=pending,draft&topic.filter=Histoire' // tri par défaut
            ],

            [
                'http://example.org/?in=event,work&status=pending,draft&topic.filter=Histoire',
                'date',
                'http://example.org/?in=event,work&status=pending,draft&topic.filter=Histoire&sort=date'
            ],
        ];
    }

    /**
     * Teste la méthode getRequest()
     *
     * @dataProvider requestProvider
     */
    public function testGetRequest($url, array $request)
    {
        $this->markTestSkipped("Test à ré-écrire, dépend de buildRequest qui n'est pas public");
        $searchUrl = new SearchUrl($url);
        $result = $searchUrl->getSearchRequest()->buildRequest();
//      var_dump($result);

        echo "\nQuery for $url :\n", json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), "\n";

        $this->assertSame($request, $result);
        //$this->assertSame($request, $searchUrl->getSearchRequest()->buildRequest());
    }

    public function requestProvider()
    {
        return [['url', ['result']]];

        $dsl = docalist('elasticsearch-query-dsl'); /* @var QueryDSL $dsl */
        return [

            // Requêtes vides
            [
                'http://example.org/',
                (new SearchRequest())->buildRequest()
            ],
            [
                'http://example.org/?',
                (new SearchRequest())->buildRequest()
            ],
            [
                'http://example.org/?q',
                (new SearchRequest())->buildRequest()
            ],
            [
                'http://example.org/?q=*',
                (new SearchRequest())->buildRequest()
            ],

            [
                'http://example.org/?q=+',
                (new SearchRequest())->buildRequest()
            ],

            [
                'http://example.org/?q=%20',
                (new SearchRequest())->buildRequest()
            ],
            [
                'http://example.org/?q&q=&q=+&q=%20',
                (new SearchRequest())->buildRequest()
            ],
            [
                'http://example.org/?q[]&q[]=&q[]=+&q[]=%20',
                (new SearchRequest())->buildRequest()
            ],

            [
                'http://example.org/?q%5B%5D&q%5B%5D=&q%5B%5D=+&q%5B%5D=%20',
                (new SearchRequest())->buildRequest()
            ],

            [
                'http://example.org/?q=%20&title=+&content&author[]=',
                (new SearchRequest())->buildRequest()
            ],

            // Une requête simple
            [
                'http://example.org/?q=hello+world!',
                (new SearchRequest())
                    ->addQuery($dsl->match('_all', 'hello world')) // "hello world!" a été tokenizé
                    ->buildRequest()
            ],

            // Page, size, sort
            [
                'http://example.org/?q=hello+world!&size=12&page=3',
                (new SearchRequest())
                    ->addQuery($dsl->match('_all', 'hello world'))
                    ->setPage(3)
                    ->setSize(12)
//                  ->setSort($sortClauses)
                    ->buildRequest()
            ],

            [
                'http://example.org/?q=hello&page=3a', // page invalide, ignorée et pas d'erreur
                (new SearchRequest())
                    ->addQuery($dsl->match('_all', 'hello'))
                    ->buildRequest()
            ],

            [
                'http://example.org/?q=hello&page=', // page invalide, ignorée et pas d'erreur
                (new SearchRequest())
                    ->addQuery($dsl->match('_all', 'hello'))
                    ->buildRequest()
            ],

            [
                'http://example.org/?q=hello&page=0', // page invalide, ignorée et pas d'erreur
                (new SearchRequest())
                    ->addQuery($dsl->match('_all', 'hello'))
                    ->buildRequest()
            ],

            [
                'http://example.org/?q=hello&page=-1', // page invalide, ignorée et pas d'erreur
                (new SearchRequest())
                    ->addQuery($dsl->match('_all', 'hello'))
                    ->buildRequest()
            ],

            [
                'http://example.org/?q=hello&page=1', // page par défaut, ignorée
                (new SearchRequest())
                    ->addQuery($dsl->match('_all', 'hello'))
                    ->buildRequest()
            ],

            [
                'http://example.org/?q=hello&size=b12', // size invalide, ignorée et pas d'erreur
                (new SearchRequest())
                    ->addQuery($dsl->match('_all', 'hello'))
                    ->buildRequest()
            ],
            [
                'http://example.org/?q=hello&size=', // size invalide, ignorée et pas d'erreur
                (new SearchRequest())
                    ->addQuery($dsl->match('_all', 'hello'))
                    ->buildRequest()
            ],
            [
                'http://example.org/?q=hello&size=0', // size valide !
                (new SearchRequest())
                    ->addQuery($dsl->match('_all', 'hello'))
                    ->setSize(0)
                    ->buildRequest()
            ],
            [
                'http://example.org/?q=hello&size=-1', // size invalide, ignorée et pas d'erreur
                (new SearchRequest())
                    ->addQuery($dsl->match('_all', 'hello'))
                    ->buildRequest()
            ],
            [
                'http://example.org/?q=hello&size=10', // size par défaut, ignorée
                (new SearchRequest())
                    ->addQuery($dsl->match('_all', 'hello'))
                    ->buildRequest()
            ],

            // Filtres standards
            [
                'http://example.org/?q=m%C3%A9nard&page=2&topic.filter=social',
                (new SearchRequest())
                    ->addQuery($dsl->match('_all', 'ménard'))
                    ->setPage(2)
                    ->addFilter($dsl->term('topic.filter', 'social'))
                    ->buildRequest()
            ],
            [
                'http://example.org/?q=m%C3%A9nard&page=2&topic.filter=hello+world!',
                (new SearchRequest())
                    ->addQuery($dsl->match('_all', 'ménard'))
                    ->setPage(2)
                    ->addFilter($dsl->term('topic.filter', 'hello world!')) // Le filtre n'a pas été tokenisé
                    ->buildRequest()
            ],

            // Filtre standard multivalué : combinés en "et"
            [
                'http://example.org/?q=m%C3%A9nard&page=2&topic.filter=hello+world!,bonjour%20monde%20%3A-)', // virgule
                (new SearchRequest())
                    ->addQuery($dsl->match('_all', 'ménard'))
                    ->setPage(2)
                    ->addFilter($dsl->term('topic.filter', 'hello world!'))
                    ->addFilter($dsl->term('topic.filter', 'bonjour monde :-)'))
                    ->buildRequest()
            ],
            [
                'http://example.org/?q=m%C3%A9nard&page=2&topic.filter[]=Hello&topic.filter[]=Bonjour', // tableau
                (new SearchRequest())
                    ->addQuery($dsl->match('_all', 'ménard'))
                    ->setPage(2)
                    ->addFilter($dsl->term('topic.filter', 'Hello'))
                    ->addFilter($dsl->term('topic.filter', 'Bonjour'))
                    ->buildRequest()
            ],

            [
                'http://example.org/?q=m%C3%A9nard&page=2&topic.filter%5B%5D=Hello&topic.filter%5B%5D=Bonjour', // tableau
                (new SearchRequest())
                    ->addQuery($dsl->match('_all', 'ménard'))
                    ->setPage(2)
                    ->addFilter($dsl->term('topic.filter', 'Hello'))
                    ->addFilter($dsl->term('topic.filter', 'Bonjour'))
                    ->buildRequest()
            ],

            [
                'http://example.org/?topic.filter[0]=Hello&topic.filter[1]=Bonjour', // tableau indexé
                (new SearchRequest())
                    ->addFilter($dsl->term('topic.filter', 'Hello'))
                    ->addFilter($dsl->term('topic.filter', 'Bonjour'))
                    ->buildRequest()
            ],
            [
                'http://example.org/?topic.filter[10]=Hello&topic.filter[4]=Bonjour', // tableau indexé
                (new SearchRequest())
                    ->addFilter($dsl->term('topic.filter', 'Hello'))        // Les indices sont ignorés
                    ->addFilter($dsl->term('topic.filter', 'Bonjour'))      // et ça ne change pas l'ordre
                    ->buildRequest()
            ],

        ];
    }
}
