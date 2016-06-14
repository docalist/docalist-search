<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2011-2016 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search\QueryParser;

use Docalist\Search\QueryDSL;

/**
 * Builder utilisé par le QueryParser pour générer la requête elasticsearch.
 */
class QueryBuilder implements Builder
{
    /**
     * Service DSL de elasticsearch.
     *
     * @var QueryDSL
     */
    protected $dsl;

    public function __construct()
    {
        $this->dsl = docalist('elasticsearch-query-dsl');
    }

    public function match($field, array $terms)
    {
        return $this->dsl->match($field, implode(' ', $terms));
    }

    public function phrase($field, array $terms)
    {
        return $this->dsl->match($field, implode(' ', $terms), 'match_phrase');
    }

    public function prefix($field, $prefix)
    {
        return $this->dsl->prefix($field, $prefix);
    }

    public function all()
    {
        return $this->dsl->matchAll();
    }

    public function bool(array $should = [], array $must = [], array $not = [])
    {
        $queries = [];
        foreach($should as $query) {
            $queries[] = $this->dsl->should($query);
        }
        foreach($must as $query) {
            $queries[] = $this->dsl->must($query);
        }
        foreach($not as $query) {
            $queries[] = $this->dsl->mustNot($query);
        }

        return $this->dsl->bool($queries);
    }

    public function range($field, $start, $end)
    {
        $range = [];
        !is_null($start) && $range['gte'] = $start;
        !is_null($end) && $range['lte'] = $end;

        return $this->dsl->range($field, $range);
    }
}