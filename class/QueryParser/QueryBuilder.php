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

namespace Docalist\Search\QueryParser;

use Docalist\Search\QueryParser\Builder;
use Docalist\Search\QueryDSL;

/**
 * Builder utilisé par le QueryParser pour générer la requête elasticsearch.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class QueryBuilder implements Builder
{
    /**
     * Initialise le builder.
     *
     * @param array $defaultSearchFields Un tableau indiquant la liste des champs par défaut.
     * Exemple : ['posttitle^2', 'content', 'name', 'topic^5'].
     */
    public function __construct(
        private QueryDSL $queryDSL,
        private array $defaultSearchFields
    ) {
    }

    /**
     * Retourne la liste des champs qui sont interrogés quand on fait une recherche "tous champs".
     *
     * @return string[] Un tableau listant les champs interrogés et leur pondération.
     * Exemple : ['posttitle^2', 'content', 'name', 'topic^5']
     */
    private function getDefaultSearchFields(): array
    {
        return $this->defaultSearchFields ?: ['posttitle'];
        //return ['posttitle^3', 'content', 'name^4', 'author^3', 'topic^5', 'othertitle^2', 'corporation^2'];
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
        ($field === '') && $field = $this->getDefaultSearchFields();
        return $this->queryDSL->multiMatch($field, implode(' ', $terms), 'best_fields', ['operator' => 'and']);
    }

    public function phrase($field, array $terms)
    {
        ($field === '') && $field = $this->getDefaultSearchFields();
        return $this->queryDSL->multiMatch($field, implode(' ', $terms), 'phrase', []);
//        return $this->queryDSL->match($field, implode(' ', $terms), 'match_phrase');
    }

    public function prefix($field, $prefix)
    {
        ($field === '') && $field = $this->getDefaultSearchFields();
        return $this->queryDSL->multiMatch($field, $prefix, 'phrase_prefix', []);
        // return $this->queryDSL->prefix($field, $prefix); // ne supporte pas plusieurs champs
    }

    public function all()
    {
        return null;// $this->queryDSL->matchAll();
    }

    public function exists($field)
    {
        return $this->queryDSL->exists($field);
    }

    public function bool(array $should = [], array $must = [], array $not = [])
    {
        $queries = [];
        foreach ($should as $query) {
            $queries[] = $this->queryDSL->should($query);
        }
        foreach ($must as $query) {
            $queries[] = $this->queryDSL->must($query);
        }
        foreach ($not as $query) {
            $queries[] = $this->queryDSL->mustNot($query);
        }

        return $this->queryDSL->bool($queries);
    }

    public function range($field, $start, $end)
    {
        $range = [];
        !is_null($start) && $range['gte'] = $start;
        !is_null($end) && $range['lte'] = $end;

        return $this->queryDSL->range($field, $range);
    }
}
