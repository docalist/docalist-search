<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2020 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Tests\SearchRequestTest;

use WP_UnitTestCase;
use Docalist\Search\SearchRequest;
use Docalist\Search\QueryDSL\Version200 as DSL;
use Docalist\Search\SearchResponse;

/**
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class SearchRequestTest extends WP_UnitTestCase
{
    // -------------------------------------------------------------------------------
    // Numéro de page
    // -------------------------------------------------------------------------------

    public function testPage()
    {
        $search = new SearchRequest();

        $this->assertSame($search->getPage(), 1);

        $ret = $search->setPage(2);
        $this->assertSame($search->getPage(), 2);
        $this->assertSame($search, $ret);

        $ret = $search->setPage('2');
        $this->assertSame($search->getPage(), 2); // convertit en (int)
        $this->assertSame($search, $ret);
    }

    public function badPageProvider()
    {
        return [[0], [''], ['a'], [-1]];
    }

    /**
     * Teste setPage() avec des paramètres invalides.
     *
     * @dataProvider badPageProvider
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Incorrect page
     */
    public function testSetPageBadPage($page)
    {
        $search = new SearchRequest();

        $search->setPage($page);
    }

    // -------------------------------------------------------------------------------
    // Taille des pages
    // -------------------------------------------------------------------------------

    public function testSize()
    {
        $search = new SearchRequest();

        $this->assertSame($search->getSize(), 10);

        $ret = $search->setSize(20);
        $this->assertSame($search->getSize(), 20);
        $this->assertSame($search, $ret);

        $ret = $search->setPage('20');
        $this->assertSame($search->getSize(), 20); // convertit en (int)
        $this->assertSame($search, $ret);
    }

    public function badSizeProvider()
    {
        return [[-1]];
    }

    /**
     * Teste setSize() avec des paramètres invalides.
     *
     * @dataProvider badSizeProvider
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Incorrect size
     */
    public function testSetSizeBadSize($size)
    {
        $search = new SearchRequest();

        $search->setSize($size);
    }

    // -------------------------------------------------------------------------------
    // Queries
    // -------------------------------------------------------------------------------

    public function testQueries()
    {
        $search = new SearchRequest();
        $dsl = new DSL();

        // pas de queries
        $this->assertSame($search->getQueries(), []);
        $this->assertFalse($search->hasQuery('toto'));
        $ret = $search->removeQuery('toto');
        $this->assertSame($search, $ret);
        $this->assertFalse($search->hasQueries());
        $this->assertTrue($search->isEmptyRequest());

        // queries standard
        $query = $dsl->term('status', 'publish');
        $search->setQueries([$query]);
        $this->assertSame($search->getQueries(), [$query]);
        $this->assertTrue($search->hasQuery($query));
        $this->assertTrue($search->hasQueries());
        $this->assertFalse($search->isEmptyRequest());

        $search->setQueries([]);
        $this->assertSame($search->getQueries(), []);
        $this->assertFalse($search->hasQuery($query));
        $this->assertFalse($search->hasQueries());
        $this->assertTrue($search->isEmptyRequest());

        $search->addQuery($query);
        $this->assertSame($search->getQueries(), [$query]);
        $this->assertTrue($search->hasQuery($query));
        $search->removeQuery($query);
        $this->assertSame($search->getQueries(), []);
        $this->assertFalse($search->hasQuery($query));
        $this->assertFalse($search->hasQueries());
        $this->assertTrue($search->isEmptyRequest());

        // Named queries
        $query = $dsl->term('status', 'publish', ['_name' => 'pub']);
        $search->addQuery($query);
        $this->assertSame($search->getQueries(), ['pub' => $query]);
        $this->assertTrue($search->hasQuery('pub'));
        $this->assertSame($search->getQuery('pub'), $query);
        $search->removeQuery('pub');
        $this->assertSame($search->getQueries(), []);
        $this->assertFalse($search->hasQuery('pub'));
        $this->assertFalse($search->hasQueries());
        $this->assertTrue($search->isEmptyRequest());
    }

    /**
     * Teste addQuery() avec une requête vide
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid query, expected array of arrays
     */
    public function testAddQueryEmptyQuery()
    {
        $search = new SearchRequest();
        $search->addQuery([]);
    }

    /**
     * Teste addQuery() si on ajoute une requête dont le nom existe déjà
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage A query named 'pub' already exists
     */
    public function testAddQueryDuplicateName()
    {
        $search = new SearchRequest();
        $dsl = new DSL();

        $query = $dsl->term('status', 'publish', ['_name' => 'pub']);
        $search->addQuery($query);

        $query = $dsl->bool([$dsl->mustNot($dsl->term('status', 'private'))], ['_name' => 'pub']);
        $search->addQuery($query);
    }

    // -------------------------------------------------------------------------------
    // Filtres utilisateurs
    // -------------------------------------------------------------------------------

    public function testFilters()
    {
        $search = new SearchRequest();
        $dsl = new DSL();

        // pas de filtres
        $this->assertSame($search->getFilters(), []);
        $this->assertFalse($search->hasFilter('toto'));
        $ret = $search->removeFilter('toto');
        $this->assertSame($search, $ret);
        $this->assertFalse($search->hasFilters());
        $this->assertTrue($search->isEmptyRequest());

        // filtres standard
        $filter = $dsl->term('status', 'publish');
        $search->setFilters([$filter]);
        $this->assertSame($search->getFilters(), [$filter]);
        $this->assertTrue($search->hasFilter($filter));
        $this->assertTrue($search->hasFilters());
        $this->assertFalse($search->isEmptyRequest());

        $search->setFilters([]);
        $this->assertSame($search->getFilters(), []);
        $this->assertFalse($search->hasFilter($filter));
        $this->assertFalse($search->hasFilters());
        $this->assertTrue($search->isEmptyRequest());

        $search->addFilter($filter);
        $this->assertSame($search->getFilters(), [$filter]);
        $this->assertTrue($search->hasFilter($filter));
        $search->removeFilter($filter);
        $this->assertSame($search->getFilters(), []);
        $this->assertFalse($search->hasFilter($filter));

        $search->toggleFilter($filter);
        $this->assertSame($search->getFilters(), [1 => $filter]);
        $this->assertTrue($search->hasFilter($filter));
        $search->toggleFilter($filter);
        $this->assertSame($search->getFilters(), []);
        $this->assertFalse($search->hasFilter($filter));

        // Named filters
        $filter = $dsl->term('status', 'publish', ['_name' => 'pub']);
        $search->addFilter($filter);
        $this->assertSame($search->getFilters(), ['pub' => $filter]);
        $this->assertTrue($search->hasFilter('pub'));
        $this->assertSame($search->getFilter('pub'), $filter);
        $this->assertTrue($search->hasFilters());
        $this->assertFalse($search->isEmptyRequest());

        $search->removeFilter('pub');
        $this->assertSame($search->getFilters(), []);
        $this->assertFalse($search->hasFilter('pub'));
        $this->assertNull($search->getFilter('pub'), $filter);

        $search->toggleFilter($filter);
        $this->assertSame($search->getFilters(), ['pub' => $filter]);
        $this->assertTrue($search->hasFilter('pub'));
        $this->assertSame($search->getFilter('pub'), $filter);

        $search->toggleFilter($filter);
        $this->assertSame($search->getFilters(), []);
        $this->assertFalse($search->hasFilter('pub'));
        $this->assertNull($search->getFilter('pub'), $filter);

    }

    /**
     * Teste addFilter() si on ajoute un filtre dont le nom existe déjà
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage A filter named 'pub' already exists
     */
    public function testAddFilterDuplicateName()
    {
        $search = new SearchRequest();
        $dsl = new DSL();

        $filter = $dsl->term('status', 'publish', ['_name' => 'pub']);
        $search->addFilter($filter);

        $filter = $dsl->bool([$dsl->mustNot($dsl->term('status', 'private'))], ['_name' => 'pub']);
        $search->addFilter($filter);
    }

    // -------------------------------------------------------------------------------
    // Filtre global (caché)
    // -------------------------------------------------------------------------------

    public function testGlobalFilter()
    {
        $search = new SearchRequest();
        $dsl = new DSL();

        $this->assertSame($search->getGlobalFilter(), null);
        $this->assertFalse($search->hasGlobalFilter());
        $this->assertTrue($search->isEmptyRequest());

        $filter = $dsl->term('status', 'publish');

        $ret = $search->setGlobalFilter($filter);
        $this->assertSame($search, $ret);
        $this->assertSame($search->getGlobalFilter(), $filter);
        $this->assertTrue($search->hasGlobalFilter());
        $this->assertFalse($search->isEmptyRequest());

        $search->setGlobalFilter();
        $this->assertNull($search->getGlobalFilter());

        $search->setGlobalFilter($filter);
        $search->setGlobalFilter([]);
        $this->assertNull($search->getGlobalFilter());
        $this->assertFalse($search->hasGlobalFilter());
        $this->assertTrue($search->isEmptyRequest());
    }

    // -------------------------------------------------------------------------------
    // Tri
    // -------------------------------------------------------------------------------
    public function testSort()
    {
        $search = new SearchRequest();

        $this->assertSame($search->getSort(), []);
        $this->assertNull($search->getSort('_score'));

        $ret = $search->addSort('_score');
        $this->assertSame($ret, $search);
        $this->assertSame($search->getSort(), ['_score' => ['order' => 'desc']]); // desc par défautpour _score
        $this->assertSame($search->getSort('_score'), ['order' => 'desc']);

        $ret = $search->setSort([]);
        $this->assertSame($ret, $search);
        $this->assertSame($search->getSort(), []);

        $search->addSort('lastupdate');
        $this->assertSame($search->getSort(), ['lastupdate' => ['order' => 'asc']]); // asc par défaut sinon
        $this->assertSame($search->getSort('lastupdate'), ['order' => 'asc']);
        $search->addSort('creation');
        $this->assertSame($search->getSort(), ['lastupdate' => ['order' => 'asc'], 'creation' => ['order' => 'asc']]);
        $this->assertSame($search->getSort('lastupdate'), ['order' => 'asc']);
        $this->assertSame($search->getSort('creation'), ['order' => 'asc']);

        $search->addSort('lastupdate', 'asc'); // clause toujours ajoutée à la fin, même si elle existe déjà
        $this->assertSame($search->getSort(), ['creation' => ['order' => 'asc'], 'lastupdate' => ['order' => 'asc']]);

        $old = $search->getSort();
        $search->setSort([]);
        $this->assertSame($search->getSort(), []);
        $search->setSort($old);
        $this->assertSame($search->getSort(), $old);
        $this->assertSame($search->getSort('lastupdate'), ['order' => 'asc']);
        $this->assertSame($search->getSort('creation'), ['order' => 'asc']);

        // Avec des options
        $sort = [
            'lastupdate' => ['order' => 'asc', 'mode' => 'min', 'unmapped_type' => 'date'],
            'creation' => ['order' => 'asc', 'missing' => '_last']
        ];
        $search->setSort($sort);
        $this->assertSame($search->getSort(), $sort);

    }

    /**
     * Teste addSort() avec un nom de champ invalide.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid sort field, expected string
     */
    public function testAddSortInvalidField()
    {
        $search = new SearchRequest();
        $search->addSort(12);
    }

    /**
     * Teste addSort() avec un ordre invalide.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid sort order for 'creation', expected string
     */
    public function testAddSortInvalidOrder1()
    {
        $search = new SearchRequest();
        $search->addSort('creation', 12);
    }

    /**
     * Teste addSort() avec un ordre invalide.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid sort order for 'creation', expected 'asc' or 'desc'
     */
    public function testAddSortInvalidOrder2()
    {
        $search = new SearchRequest();
        $search->addSort('creation', 'descending');
    }

    /**
     * Teste addSort() avec des options non autorisées.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid sort options for field 'creation': hello
     */
    public function testAddSortInvalidOptions()
    {
        $search = new SearchRequest();
        $search->addSort('creation', 'asc', ['hello' => 'world']);
    }

    // -------------------------------------------------------------------------------
    // Liste des champs retournés
    // -------------------------------------------------------------------------------

    public function testSourceFilter()
    {
        $search = new SearchRequest();

        $this->assertFalse($search->getSourceFilter());

        // bool
        $search->setSourceFilter(true);
        $this->assertTrue($search->getSourceFilter());

        $search->setSourceFilter(false);
        $this->assertFalse($search->getSourceFilter());

        // string
        $search->setSourceFilter('');
        $this->assertFalse($search->getSourceFilter());

        $search->setSourceFilter('*');
        $this->assertTrue($search->getSourceFilter());

        $search->setSourceFilter('creation');
        $this->assertSame($search->getSourceFilter(), 'creation');

        $search->setSourceFilter(' creation , date.*  ');
        $this->assertSame($search->getSourceFilter(), ['creation', 'date.*']);

        // array
        $search->setSourceFilter([]);
        $this->assertFalse($search->getSourceFilter());

        $search->setSourceFilter(['*']);
        $this->assertTrue($search->getSourceFilter());

        $search->setSourceFilter(['creation']);
        $this->assertSame($search->getSourceFilter(), 'creation');

        $search->setSourceFilter([' creation ', ' date.*  ']);
        $this->assertSame($search->getSourceFilter(), ['creation', 'date.*']);
    }

    /**
     * Teste setSourceFilter() avec un filtre incorrect.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid source filter, expected bool, string or array
     */
    public function testSourceFilterInvalidFilter()
    {
        $search = new SearchRequest();
        $search->setSourceFilter(12);
    }

    // -------------------------------------------------------------------------------
    // Exécution
    // -------------------------------------------------------------------------------

    public function testExecute()
    {
        $search = new SearchRequest();
        $dsl = new DSL();

        $this->assertFalse($search->hasErrors());

        $results = $search->addQuery($dsl->match('title', 'bonjour'));
        $results = $search->addQuery($dsl->match('content', 'wordpress'));
        $results = $search->addFilter($dsl->term('createdby', 'dmenard'));
        $results = $search->addFilter($dsl->term('type', 'post'));
        $results = $search->setGlobalFilter($dsl->term('status', 'publish'));
        $results->setPage(1)->setSize(20);
        $search->setSort(['creation' => ['order' => 'asc'], 'lastupdate' => ['order' => 'asc']]);

        $results = $search->execute();
        $this->assertInstanceOf(SearchResponse::class, $results);
        $this->assertFalse($search->hasErrors());

        $search = new SearchRequest();
        $search->addQuery(['aa' => []]);
        $results = $search->execute();
        $this->assertNull($results);
        $this->assertTrue($search->hasErrors());
    }

    /**
     * Teste execute() avec un type incorrect.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid search type, expected query_then_fetch or dfs_query_then_fetch
     */
    public function testExecuteInvalidType()
    {
        $search = new SearchRequest();
        $search->execute('dfs_then_fetch');
    }

    /**
     * Teste execute() avec une requête incorrecte.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid search type, expected query_then_fetch or dfs_query_then_fetch
     */
    public function testExecuteError()
    {
        $search = new SearchRequest();
        $search->execute('dfs_then_fetch');
    }
}
