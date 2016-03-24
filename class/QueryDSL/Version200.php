<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2013-2015 Daniel Ménard
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
        // Liste des paramètres autorisés (déterminée en regardant le code source de MatchQueryParser.java)
        $accept = [
            '_name', 'boost',
        ];

        // Vérifie les paramètres
        if ($parameters && $bad = array_diff(array_keys($parameters), $accept)) {
            throw new InvalidArgumentException("Invalid matchAll parameters: " . implode(', ', $bad));
        }

        // Ok
        return ['match_all' => $parameters];
    }

    public function matchNone(array $parameters = []) // n'existe pas avec ES < 5.0
    {
        return $this->term('', '-', $parameters);
    }

    // -------------------------------------------------------------------------------
    // Full text queries
    // -------------------------------------------------------------------------------

    public function match($query, $field = '_all', $type = 'match', array $parameters = [])
    {
        // Liste des paramètres autorisés (déterminée en regardant le code source de MatchQueryParser.java)
        $accept = [
         // 'query', 'type', // fixés par nous donc pas autorisés dans $parameters
            'operator', 'analyzer', 'slop', 'minimum_should_match', 'fuzziness', 'fuzzy_rewrite', 'prefix_length',
            'fuzzy_transpositions', 'max_expansions', 'lenient', 'cutoff_frequency', 'zero_terms_query',
            '_name', 'boost',
        ];

        // Vérifie le type
        if (! in_array($type, ['match', 'match_phrase', 'match_phrase_prefix'])) {
            throw new InvalidArgumentException("Invalid match type: '$type'");
        }

        // Vérifie les paramètres
        if ($parameters && $bad = array_diff(array_keys($parameters), $accept)) {
            throw new InvalidArgumentException("Invalid match parameters: " . implode(', ', $bad));
        }

        // Construit les arguments de la requête. Génère la version simplifiée si on n'a aucun paramètre
        $args = $parameters ? (['query' => $query] + $parameters) : $query;

        // Ok
        return [$type => [$field => $args]];
    }

    public function multiMatch($query, $fields = '_all', $type = 'best_fields', array $parameters = [])
    {
        // Liste des paramètres autorisés (déterminée en regardant le code source de MultiMatchQueryParser.java)
        $accept = [
          //'query', 'fields', 'type', // fixés par nous donc pas autorisés dans $parameters
            'analyzer', 'cutoff_frequency', 'fuzziness', 'fuzzy_rewrite', 'use_dis_max', 'lenient', 'max_expansions',
            'minimum_should_match', 'operator', 'prefix_length', 'slop', 'tie_breaker', 'zero_terms_query',
            'boost', '_name',
        ];

        // Vérifie le/les noms de champs indiqués
        $fields = (array) $this->checkFields($fields);

        // Vérifie le type
        if (! in_array($type, ['best_fields', 'most_fields', 'cross_fields', 'phrase', 'phrase_prefix'])) {
            throw new InvalidArgumentException("Invalid MultiMatch type: '$type'");
        }

        // Vérifie les paramètres
        if ($parameters && $bad = array_diff(array_keys($parameters), $accept)) {
            throw new InvalidArgumentException("Invalid MultiMatch parameters: " . implode(', ', $bad));
        }

        // Construit les arguments de la requête
        $args = ['type' => $type, 'query' => $query, 'fields' => $fields] + $parameters;

        // Ok
        return ['multi_match' => $args];
    }

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

    public function queryString($query, $fields = '_all', $defaultOperator = 'or', array $parameters = [])
    {
        // Liste des paramètres autorisés (déterminée en regardant le code source de QueryStringQueryParser.java)
        $accept = [
            // 'query', 'fields', 'default_field', 'default_operator', // fixés par nous, pas autorisés dans $parameters
            'analyzer', 'quote_analyzer', 'allow_leading_wildcard',
            'auto_generate_phrase_queries', 'auto_generated_phrase_queries', // avec un "d" dans ES 5.0 alpha ?
            'max_determined_states', 'lowercase_expanded_terms', 'enable_position_increment', 'escape',
            'use_dis_max', 'fuzzy_prefix_length', 'fuzzy_max_expansions', 'fuzzy_rewrite', 'phrase_slop',
            'fuzziness', 'tie_breaker', 'analyze_wildcard', 'rewrite', 'minimum_should_match', 'quote_field_suffix',
            'lenient', 'locale', 'time_zone',
            'boost', '_name',
        ];

        // Vérifie le/les noms de champs indiqués
        $fields = $this->checkFields($fields);

        // Vérifie les paramètres
        if ($parameters && $bad = array_diff(array_keys($parameters), $accept)) {
            throw new InvalidArgumentException("Invalid QueryString parameters: " . implode(', ', $bad));
        }

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
        // Liste des paramètres autorisés (déterminée en regardant le code source de SimpleQueryStringParser.java)
        $accept = [
            // 'query', 'fields', 'default_operator', // fixés par nous, pas autorisés dans $parameters
            'analyzer', 'minimum_should_match', 'flags', 'locale', 'lowercase_expanded_terms',
            'lenient', 'analyze_wildcard', 'boost', '_name',
        ];

        // Vérifie le/les noms de champs indiqués
        $fields = (array) $this->checkFields($fields);

        // Vérifie les paramètres
        if ($parameters && $bad = array_diff(array_keys($parameters), $accept)) {
            throw new InvalidArgumentException("Invalid SimpleQueryString parameters: " . implode(', ', $bad));
        }

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

    public function term($query, $field = '_all', array $parameters = [])
    {
        // Convenience : si on est appellé avec plusieurs termes, génère une terms query
        if (is_array($query)) {
            if (count($query) !== 1) {
                return $this->terms($query, $field, $parameters);
            }
            $query = reset($query);
        }

        // Liste des paramètres autorisés (déterminée en regardant le code source de TermQueryParser.java)
        $accept = [
            // 'term', 'value', // fixés par nous, pas autorisés dans $parameters
            'boost', '_name',
        ];

        // Vérifie les paramètres
        if ($parameters && $bad = array_diff(array_keys($parameters), $accept)) {
            throw new InvalidArgumentException("Invalid Term parameters: " . implode(', ', $bad));
        }

        // Construit les arguments de la requête. Génère la version simplifiée si on n'a aucun paramètre
        $args = $parameters ? (['value' => $query] + $parameters) : $query;

        // Ok
        return ['term' => [$field => $args]];
    }

    public function terms($query, $field = '_all', array $parameters = [])
    {
        // Convenience : si on est appellé un seul terme, génère une term query
        if (is_scalar($query) || (is_array($query) && count($query) === 1)) {
            return $this->term($query, $field, $parameters);
        }

        // Liste des paramètres autorisés (déterminée en regardant le code source de TermsQueryParser.java)
        // + indices/TermsLookup.java
        $accept = [
            // 'term', 'value', // fixés par nous, pas autorisés dans $parameters
            'boost', '_name',
            'index', 'type', 'id', 'routing', 'path',
        ];

        // Vérifie les paramètres
        if ($parameters && $bad = array_diff(array_keys($parameters), $accept)) {
            throw new InvalidArgumentException("Invalid Terms parameters: " . implode(', ', $bad));
        }

        // Construit les arguments de la requête. Génère la version simplifiée si on n'a aucun paramètre
        $args = $parameters ? (['value' => $query] + $parameters) : $query;

        // Ok
        return ['terms' => [$field => $args]];
    }

    public function range(array $query, $field = '_all', array $parameters = [])
    {
        // Vérifie qu'on a des clauses
        if (empty($query)) {
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
        if ($bad = array_diff(array_keys($query), $accept)) {
            throw new InvalidArgumentException("Invalid range operators: " . implode(', ', $bad));
        }

        // Liste des paramètres autorisés (déterminée en regardant le code source de RangeQueryParser.java)
        $accept = [
            'time_zone', 'format',                  // Date range only
            'boost', '_name',
        ];

        // Vérifie les paramètres
        if ($bad = array_diff(array_keys($parameters), $accept)) {
            throw new InvalidArgumentException("Invalid range parameters: " . implode(', ', $bad));
        }

        return ['range' => [$field => $query + $parameters]];
    }

    public function exists($field, array $parameters = [])
    {
        // Liste des paramètres autorisés (déterminée en regardant le code source de ExistsQueryParser.java)
        $accept = [
            'boost', '_name',
        ];

        // Vérifie les paramètres
        if ($parameters && $bad = array_diff(array_keys($parameters), $accept)) {
            throw new InvalidArgumentException("Invalid Terms parameters: " . implode(', ', $bad));
        }

        // Construit les arguments de la requête
        $args = ['field' => $field] + $parameters;

        // Ok
        return ['exists' => $args];
    }

    public function missing($field, array $parameters = [])
    {
        return self::bool([self::mustNot(self::exists($field, $parameters))]);
    }

    public function prefix($query, $field = '_all', array $parameters = [])
    {
        // Liste des paramètres autorisés (déterminée en regardant le code source de ExistsQueryParser.java)
        $accept = [
         // 'value', 'prefix', // fixés par nous, pas autorisés dans $parameters
            'boost', '_name',
            'rewrite',
        ];

        // Vérifie les paramètres
        if ($parameters && $bad = array_diff(array_keys($parameters), $accept)) {
            throw new InvalidArgumentException("Invalid Prefix parameters: " . implode(', ', $bad));
        }

        // Construit les arguments de la requête. Génère la version simplifiée si on n'a aucun paramètre
        $args = $parameters ? (['prefix' => $query] + $parameters) : $query;

        // Ok
        return ['prefix' => [$field => $args]];
    }

    public function wildcard($query, $field = '_all', array $parameters = [])
    {
        // Liste des paramètres autorisés (déterminée en regardant le code source de WildcardQueryParser.java)
        $accept = [
         // 'value', 'wildcard', // fixés par nous, pas autorisés dans $parameters
            'boost', '_name',
            'rewrite',
        ];

        // Vérifie les paramètres
        if ($parameters && $bad = array_diff(array_keys($parameters), $accept)) {
            throw new InvalidArgumentException("Invalid Wildcard parameters: " . implode(', ', $bad));
        }

        // Construit les arguments de la requête. Génère la version simplifiée si on n'a aucun paramètre
        $args = $parameters ? (['wildcard' => $query] + $parameters) : $query;

        // Ok
        return ['wildcard' => [$field => $args]];
    }

    // regexpQuery
    // fuzzyQuery

    public function type($query, array $parameters = [])
    {
        // Liste des paramètres autorisés (déterminée en regardant le code source de WildcardQueryParser.java)
        $accept = [
         // 'value', // fixés par nous, pas autorisés dans $parameters
            'boost', '_name',
        ];

        // Vérifie les paramètres
        if ($parameters && $bad = array_diff(array_keys($parameters), $accept)) {
            throw new InvalidArgumentException("Invalid Type parameters: " . implode(', ', $bad));
        }

        // Construit les arguments de la requête
        $args = ['value' => $query] + $parameters;

        // Ok
        return ['type' => $args];
    }

    public function ids($id, $type = null, array $parameters = [])
    {
        // Liste des paramètres autorisés (déterminée en regardant le code source de IdsQueryParser.java)
        $accept = [
            // 'values', 'type', // fixés par nous, pas autorisés dans $parameters
            'boost', '_name',
        ];

        // Vérifie les paramètres
        if ($parameters && $bad = array_diff(array_keys($parameters), $accept)) {
            throw new InvalidArgumentException("Invalid Ids parameters: " . implode(', ', $bad));
        }

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
                throw new InvalidArgumentException('Invalid boolean clause');
            }
            $type = key($clause);
            if (! is_string($type) || ! in_array($type, ['must', 'filter', 'should', 'must_not'], true)) {
                throw new InvalidArgumentException("Invalid boolean clause type: $type");
            }

            $merge[$type][] = reset($clause);
        }

        // Liste des paramètres autorisés (déterminée en regardant le code source de IdsQueryParser.java)
        $accept = [
            'disable_coord', 'minimum_should_match', 'minimum_number_should_match', 'adjust_pure_negative',
            'boost', '_name',
        ];

        // Vérifie les paramètres
        if ($parameters && $bad = array_diff(array_keys($parameters), $accept)) {
            throw new InvalidArgumentException("Invalid boolean parameters: " . implode(', ', $bad));
        }

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
}
