<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2013-2017 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search\QueryDSL;

use InvalidArgumentException;
use Docalist\Search\QueryDSL;

/**
 * Méthodes utilitaires pour manipuler le Query DSL Elasticsearch.
 */
class Version200 implements QueryDSL
{
    public function getVersion()
    {
        return '2.x.x';
    }

    // -------------------------------------------------------------------------------
    // Match All, Match None
    // -------------------------------------------------------------------------------

    public function matchAll(array $parameters = [])
    {
        // Vérifie les paramètres autorisés (déterminée en regardant le code source de MatchQueryParser.java)
        $this->checkParameters(__FUNCTION__, $parameters, [
            '_name', 'boost',
        ]);

        // Ok
        return ['match_all' => $parameters];
    }

    public function matchNone(array $parameters = []) // n'existe pas avec ES < 5.0
    {
        return $this->term('-', '', $parameters);
    }

    // -------------------------------------------------------------------------------
    // Full text queries
    // -------------------------------------------------------------------------------

    public function match($field, $term, $type = 'match', array $parameters = [])
    {
        // Vérifie le type
        if (! in_array($type, ['match', 'match_phrase', 'match_phrase_prefix'])) {
            throw new InvalidArgumentException("Invalid match type: '$type'");
        }

        // Vérifie les paramètres autorisés (déterminée en regardant le code source de MatchQueryParser.java)
        $this->checkParameters(__FUNCTION__, $parameters, [
         // 'query', 'type', // fixés par nous donc pas autorisés dans $parameters
            'operator', 'analyzer', 'slop', 'minimum_should_match', 'fuzziness', 'fuzzy_rewrite', 'prefix_length',
            'fuzzy_transpositions', 'max_expansions', 'lenient', 'cutoff_frequency', 'zero_terms_query',
            '_name', 'boost',
        ]);

        // Construit les arguments de la requête. Génère la version simplifiée si on n'a aucun paramètre
        $args = $parameters ? (['query' => $term] + $parameters) : $term;

        // Ok
        return [$type => [$field => $args]];
    }

    public function multiMatch($fields, $terms, $type = 'best_fields', array $parameters = [])
    {
        // Vérifie le/les noms de champs indiqués
        $fields = (array) $this->checkFields($fields);

        // Vérifie le type
        if (! in_array($type, ['best_fields', 'most_fields', 'cross_fields', 'phrase', 'phrase_prefix'])) {
            throw new InvalidArgumentException("Invalid multiMatch type: '$type'");
        }

        // Vérifie les paramètres autorisés (déterminée en regardant le code source de MultiMatchQueryParser.java)
        $this->checkParameters(__FUNCTION__, $parameters, [
          //'query', 'fields', 'type', // fixés par nous donc pas autorisés dans $parameters
            'analyzer', 'cutoff_frequency', 'fuzziness', 'fuzzy_rewrite', 'use_dis_max', 'lenient', 'max_expansions',
            'minimum_should_match', 'operator', 'prefix_length', 'slop', 'tie_breaker', 'zero_terms_query',
            'boost', '_name',
        ]);

        // Construit les arguments de la requête
        $args = ['type' => $type, 'query' => $terms, 'fields' => $fields] + $parameters;

        // Ok
        return ['multi_match' => $args];
    }

    public function queryString($query, $fields = '_all', $defaultOperator = 'or', array $parameters = [])
    {
        // Vérifie le/les noms de champs indiqués
        $fields = $this->checkFields($fields);

        // Vérifie les paramètres autorisés (déterminée en regardant le code source de QueryStringQueryParser.java)
        $this->checkParameters(__FUNCTION__, $parameters, [
            // 'query', 'fields', 'default_field', 'default_operator', // fixés par nous, pas autorisés dans $parameters
            'analyzer', 'quote_analyzer', 'allow_leading_wildcard',
            'auto_generate_phrase_queries', 'auto_generated_phrase_queries', // avec un "d" dans ES 5.0 alpha ?
            'max_determined_states', 'lowercase_expanded_terms', 'enable_position_increment', 'escape',
            'use_dis_max', 'fuzzy_prefix_length', 'fuzzy_max_expansions', 'fuzzy_rewrite', 'phrase_slop',
            'fuzziness', 'tie_breaker', 'analyze_wildcard', 'rewrite', 'minimum_should_match', 'quote_field_suffix',
            'lenient', 'locale', 'time_zone',
            'boost', '_name',
        ]);

        // Construit les arguments de la requête
        $args = ['query' => $query];
        is_string($fields) ? $args['default_field'] = $fields : $args['fields'] = $fields;
        $defaultOperator !== 'or' && $args['default_operator'] = $defaultOperator;
        $args += $parameters;

        // Ok
        return ['query_string' => $args];
    }

    public function simpleQueryString($query, $fields = '_all', $defaultOperator = 'or', array $parameters = [])
    {
        // Vérifie le/les noms de champs indiqués
        $fields = (array) $this->checkFields($fields);

        // Vérifie les paramètres autorisés (déterminée en regardant le code source de SimpleQueryStringParser.java)
        $this->checkParameters(__FUNCTION__, $parameters, [
            // 'query', 'fields', 'default_operator', // fixés par nous, pas autorisés dans $parameters
            'analyzer', 'minimum_should_match', 'flags', 'locale', 'lowercase_expanded_terms',
            'lenient', 'analyze_wildcard', 'boost', '_name',
        ]);

        // Construit les arguments de la requête
        $args = ['query' => $query, 'fields' => $fields];
        $defaultOperator !== 'or' && $args['default_operator'] = $defaultOperator;
        $args += $parameters;

        // Ok
        return ['simple_query_string' => $args];
    }

    // -------------------------------------------------------------------------------
    // Term level queries
    // -------------------------------------------------------------------------------

    public function term($field, $term, array $parameters = [])
    {
        // Convenience : si on est appellé avec plusieurs termes, génère une terms query
        if (is_array($term)) {
            if (count($term) !== 1) {
                return $this->terms($field, $term, $parameters);
            }
            $term = reset($term);
        }

        // Vérifie les paramètres autorisés (déterminée en regardant le code source de TermQueryParser.java)
        $this->checkParameters(__FUNCTION__, $parameters, [
            // 'term', 'value', // fixés par nous, pas autorisés dans $parameters
            'boost', '_name',
        ]);

        // Construit les arguments de la requête. Génère la version simplifiée si on n'a aucun paramètre
        $args = $parameters ? (['value' => $term] + $parameters) : $term;

        // Ok
        return ['term' => [$field => $args]];
    }

    public function terms($field, $terms, array $parameters = [])
    {
        // Convenience : si on est appellé un seul terme, génère une term query
        if (is_scalar($terms) || (is_array($terms) && count($terms) === 1)) {
            return $this->term($field, $terms, $parameters);
        }

        // Vérifie les paramètres autorisés (déterminée en regardant le code source de TermsQueryParser.java)
        // + indices/TermsLookup.java
        $this->checkParameters(__FUNCTION__, $parameters, [
            // 'term', 'value', // fixés par nous, pas autorisés dans $parameters
            'boost', '_name',
            'index', 'type', 'id', 'routing', 'path',
        ]);

        // Construit les arguments de la requête
        $args = [$field => $terms];
        !empty($parameters) && $args += $parameters;

        // Ok
        return ['terms' => $args];
    }

    public function range($field, array $clauses, array $parameters = [])
    {
        // Vérifie qu'on a des clauses
        if (empty($clauses)) {
            throw new InvalidArgumentException('Invalid range (empty)');
        }

        // Liste des types de clauses autorisées
        $accept = [
         // 'from', 'to',                           // deprecated in 0.94
         // 'include_lower', 'include_upper',       // deprecated in 0.94
         // 'ge',                                   // alias de gte, non documenté
         // 'le',                                   // alias de lte, non documenté
            'gte','gt',
            'lte', 'lt',
        ];

        // Vérifie les clauses
        if ($bad = array_diff(array_keys($clauses), $accept)) {
            throw new InvalidArgumentException("Invalid range operators: " . implode(', ', $bad));
        }

        // Liste des paramètres autorisés (déterminée en regardant le code source de RangeQueryParser.java)
        $this->checkParameters(__FUNCTION__, $parameters, [
            'time_zone', 'format',                  // Date range only
            'boost', '_name',
        ]);

        // Ok
        return ['range' => [$field => $clauses + $parameters]];
    }

    public function exists($field, array $parameters = [])
    {
        // Vérifie les paramètres autorisés (déterminée en regardant le code source de ExistsQueryParser.java)
        $this->checkParameters(__FUNCTION__, $parameters, [
            'boost', '_name',
        ]);

        // Construit les arguments de la requête
        $args = ['field' => $field] + $parameters;

        // Ok
        return ['exists' => $args];
    }

    public function missing($field, array $parameters = [])
    {
        return self::bool([self::mustNot(self::exists($field))], $parameters);
    }

    public function prefix($field, $prefix, array $parameters = [])
    {
        // Vérifie les paramètres autorisés (déterminée en regardant le code source de PrefixQueryParser.java)
        $this->checkParameters(__FUNCTION__, $parameters, [
         // 'value', 'prefix', // fixés par nous, pas autorisés dans $parameters
            'boost', '_name',
            'rewrite',
        ]);

        // Construit les arguments de la requête. Génère la version simplifiée si on n'a aucun paramètre
        $args = $parameters ? (['prefix' => $prefix] + $parameters) : $prefix;

        // Ok
        return ['prefix' => [$field => $args]];
    }

    public function wildcard($field, $wildcard, array $parameters = [])
    {
        // Vérifie les paramètres autorisés (déterminée en regardant le code source de WildcardQueryParser.java)
        $this->checkParameters(__FUNCTION__, $parameters, [
         // 'value', 'wildcard', // fixés par nous, pas autorisés dans $parameters
            'boost', '_name',
            'rewrite',
        ]);

        // Construit les arguments de la requête. Génère la version simplifiée si on n'a aucun paramètre
        $args = $parameters ? (['wildcard' => $wildcard] + $parameters) : $wildcard;

        // Ok
        return ['wildcard' => [$field => $args]];
    }

    // regexpQuery
    // fuzzyQuery

    public function type($esType, array $parameters = [])
    {
        // Vérifie les paramètres autorisés (déterminée en regardant le code source de TypeQueryParser.java)
        $this->checkParameters(__FUNCTION__, $parameters, [
         // 'value', // fixés par nous, pas autorisés dans $parameters
            'boost', '_name',
        ]);

        // Construit les arguments de la requête
        $args = ['value' => $esType] + $parameters;

        // Ok
        return ['type' => $args];
    }

    public function ids($id, $type = null, array $parameters = [])
    {
        // Vérifie les paramètres autorisés (déterminée en regardant le code source de IdsQueryParser.java)
        $this->checkParameters(__FUNCTION__, $parameters, [
            // 'values', 'type', // fixés par nous, pas autorisés dans $parameters
            'boost', '_name',
        ]);

        // Construit les arguments de la requête
        $args = ['values' => (array) $id];
        !is_null($type) && $args['type'] = $type;
        $args += $parameters;

        // Ok
        return ['ids' => $args];
    }

    // -------------------------------------------------------------------------------
    // Compound queries
    // -------------------------------------------------------------------------------

    public function bool(array $clauses, array $parameters = [])
    {
        // Fusionne les clauses par opérateur
        $merge = [];
        foreach ($clauses as $key => $clause) {
            if (!is_int($key) || !is_array($clause) || count($clause) !== 1) {
                throw new InvalidArgumentException('Invalid bool clause');
            }
            $type = key($clause);
            if (! is_string($type) || ! in_array($type, ['must', 'filter', 'should', 'must_not'], true)) {
                throw new InvalidArgumentException("Invalid bool clause type: $type");
            }

            $merge[$type][] = reset($clause);
        }

        // Liste des paramètres autorisés (déterminée en regardant le code source de IdsQueryParser.java)
        $this->checkParameters(__FUNCTION__, $parameters, [
            'disable_coord', 'minimum_should_match', 'minimum_number_should_match', 'adjust_pure_negative',
            'boost', '_name',
        ]);

        // Ok
        return ['bool' => $merge + $parameters];
    }

    // -------------------------------------------------------------------------------
    // Clauses booléennes pour la méthode bool()
    // -------------------------------------------------------------------------------

    public function must(array $clause)
    {
        return ['must' => $clause];
    }

    public function filter(array $clause)
    {
        return ['filter' => $clause];
    }

    public function should($clause)
    {
        return ['should' => $clause];
    }

    public function mustNot($clause)
    {
        return ['must_not' => $clause];
    }

    // -------------------------------------------------------------------------------
    // Joining queries
    // -------------------------------------------------------------------------------

    public function nested($path, array $query, array $parameters = [])
    {
        // Vérifie les paramètres autorisés (déterminés en regardant le code source de NestedQueryParser.java)
        $this->checkParameters(__FUNCTION__, $parameters, [
            // 'path', 'query', // fixés par nous, pas autorisés dans $parameters
            'score_mode', // avg (par défaut), sum, min, max ou none.
            'inner_hits',
            'boost', '_name',

            // 'filter' : existait en 1.x mais plus maintenant
            // scoreMode : deprecated
        ]);

        // Construit les arguments de la requête
        $args = ['path' => $path, 'query' => $query]  + $parameters;

        // Ok
        return ['nested' => $args];
    }

    // -------------------------------------------------------------------------------
    // Méthodes protégées (vérification des paramètres)
    // -------------------------------------------------------------------------------

    /**
     * Valide et normalise la liste de champs passée en paramétre.
     *
     * @param string|array $fields Un tableau contenant une liste de champs ou une chaine contenant les noms des champs
     * séparés par une virgule.
     *
     * @return string|array Retourne une chaine si on a un nom de champ unique, un tableau de noms de champs sinon.
     *
     * @throws InvalidArgumentException Si $fields n'est ni un tableau ni une chaine
     */
    protected function checkFields($fields)
    {
        is_string($fields) && $fields = array_map('trim', explode(',', $fields));
        if (! is_array($fields)) {
            throw new InvalidArgumentException('Invalid fields parameter, expected string or array');
        }
        count($fields) === 1 && $fields = reset($fields);

        return $fields;
    }

    /**
     * Génère une exception si certains des paramètres passés à une requête ne sont pas autorisés.
     *
     * @param string $queryName Nom de la requête.
     * @param array $parameters Liste des paramètres à vérifier.
     * @param array $accept Liste des paramètres autorisés.
     *
     * @throws InvalidArgumentException Si $parameters contient des paramètres non autorisés.
     */
    protected function checkParameters($queryName, array $parameters, array $accept)
    {
        if (!empty($parameters) && $bad = array_diff(array_keys($parameters), $accept)) {
            throw new InvalidArgumentException("Invalid $queryName parameters: " . implode(', ', $bad));
        }
    }
}
