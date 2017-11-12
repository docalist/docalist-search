<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2011-2017 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search\QueryParser;

use Docalist\Search\QueryParser\Builder;
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
        // Il faudrait qu'on ait un objet FieldsManager chargé de traduire les champs de recherche manipulés
        // par l'utilisateur en champs elasticsearch.
        // - isField($name) : indique si c'est un champ/un filtre ou pas (isField('page') : false)
        //   (permettrait à SearchUrl de savoir quels paramètres prendre en compte et au QueryParser de savoir si
        //   ce qui précède un signe ":" est ou nom un champ (exemple : "android / ios: a comparaison")
        // - getType($name) : type de champ (champ simple, filtre en et, filtre en ou)
        // - getDestination($name) : retourne les champs ES qui sont interrogés. Exemple : '' => ['title^2', 'content']
        //   (resolve ?)
        // - canRange($name) : indique si le champ supporte ou non les requêtes de type range ?
        // + gestion de "triggers" : by:me -> createdby:login, today -> date en cours, etc.
        ($field === '') && $field = ['posttitle^2', 'content', 'name'];
        return $this->dsl->multiMatch($field, implode(' ', $terms), 'best_fields', ['operator' => 'and']);
    }

    public function phrase($field, array $terms)
    {
        ($field === '') && $field = ['posttitle^2', 'content', 'name'];
        return $this->dsl->multiMatch($field, implode(' ', $terms), 'phrase', []);
//        return $this->dsl->match($field, implode(' ', $terms), 'match_phrase');
    }

    public function prefix($field, $prefix)
    {
        ($field === '') && $field = ['posttitle^2', 'content', 'name'];
        return $this->dsl->multiMatch($field, $prefix, 'phrase_prefix', []);
        // return $this->dsl->prefix($field, $prefix); // ne supporte pas plusieurs champs
    }

    public function all()
    {
        return null;// $this->dsl->matchAll();
    }

    public function exists($field)
    {
        return $this->dsl->exists($field);
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
        !is_null($end) && $range['lt'] = $end;

        return $this->dsl->range($field, $range);
    }
}
