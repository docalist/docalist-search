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
namespace Docalist\Search;

use InvalidArgumentException;

/**
 * Méthodes utilitaires pour manipuler le Query DSL Elasticsearch.
 */
interface QueryDSL
{
    /**
     * Retourne le numéro de version de la classe DSL.
     *
     * @return string Un numéro de version de la forme '2.x.x'.
     */
    public function getVersion();

    // Les méthodes sont listées dans l'ordre de la doc (cf. sommaire)
    // https://www.elastic.co/guide/en/elasticsearch/reference/master/query-dsl.html

    // -------------------------------------------------------------------------------
    // Match All, Match None
    // -------------------------------------------------------------------------------

    /**
     * Crée une requête qui retourne tous les documents.
     *
     * @param array $parameters Paramètres additionnels de la requête.
     *
     * @return array Un tableau décrivant une requête de type "Match All".
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/master/query-dsl-match-all-query.html
     */
    public function matchAll(array $parameters = []);

    /**
     * Crée une requête qui ne retourne aucun document.
     *
     * @param array $parameters Paramètres additionnels de la requête.
     *
     * @return array Un tableau décrivant une requête de type "Match None".
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/master/query-dsl-match-all-query.html
     */
    public function matchNone(array $parameters = []);

    // -------------------------------------------------------------------------------
    // Full text queries
    // -------------------------------------------------------------------------------

    /**
     * Crée une requête qui analyse le texte indiqué et recherche les documents correspondants dans un champ unique.
     *
     * @param string $query Le texte recherché.
     * @param string $field Le champ sur lequel porte la recherche.
     * @param string $type Le type de requête match à générer :
     * - 'match' : (par défaut) une booléenne qui combine les termes avec des clauses 'should' par défaut ou avec
     *             des clauses 'must' si vous indiquez 'operator' => 'and' dans les paramètres.
     * - 'match_phrase' : une recherche par phrase.
     * - 'match_phrase_prefix' : une recherche par phrase qui gère le dernier mot de la phrase comme un préfixe.
     * @param array $parameters Paramètres additionnels de la requête.
     *
     * @return array Un tableau décrivant une requête de type "Match".
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/master/query-dsl-match-query.html
     */
    public function match($query, $field = '_all', $type = 'match', array $parameters = []);

    /**
     * Crée une requête qui analyse le texte indiqué et recherche les documents correspondants dans plusieurs champs.
     *
     * @param string $query Le texte recherché.
     * @param string|array $fields Les champ sur lesquels porte la recherche.
     * @param string $type Le type de requête match à générer :
     * - 'best_fields' : (par défaut) Recherche les termes dans tous les champs et utilise le score du meilleur champ.
     * - 'most_fields' : Recherche les termes dans tous les champs mais combine les scores obtenus pour chaque champ.
     * - 'cross_fields' : Traite les termes avec le même analyseur comme s'il s'agissait d'un champ unique.
     * - 'phrase' : Fait une recherche par phrase dans chaque champ et combine les scores obtenus.
     * - 'phrase_prefix' : Fait une recherche phrase + préfixe dans chaque champ et combine les scores obtenus.
     * @param array $parameters Paramètres additionnels de la requête.
     *
     * @return array Un tableau décrivant une requête de type "Match".
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/master/query-dsl-match-query.html
     */
    public function multiMatch($query, $fields = '_all', $type = 'best_fields', array $parameters = []);

    // public function commonTerms(); // plus ou moins intégré dans match/multiMatch donc pas très utile

    /**
     * Crée une requête qui utilise le query parser de lucene pour analyser l'équation de recherche passée en
     * paramètre.
     *
     * @param string $query L'équation de recherche à exécuter.
     * @param string $fields Les champs par défaut sur lesquels porte la recherche.
     * @param string $defaultOperator L'opérateur par défaut : 'or' ou 'and', 'or' par défaut.
     * @param array $parameters Paramètres additionnels de la requête.
     *
     * @return array Un tableau décrivant une requête de type "QueryString".
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/master/query-dsl-query-string-query.html
     * @see https://lucene.apache.org/core/5_5_0/queryparser/org/apache/lucene/queryparser/classic/package-summary.html
     */
    public function queryString($query, $fields = '_all', $defaultOperator = 'or', array $parameters = []);

    /**
     * Crée une requête qui utilise le query parser simplifié de lucene pour analyser l'équation de recherche passée
     * en paramètre.
     *
     * @param string $query L'équation de recherche à exécuter.
     * @param string $fields Les champs par défaut sur lesquels porte la recherche.
     * @param string $defaultOperator L'opérateur par défaut : 'or' ou 'and', 'or' par défaut.
     * @param array $parameters Paramètres additionnels de la requête.
     *
     * @return array Un tableau décrivant une requête de type "QueryString".
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/master/query-dsl-simple-query-string-query.html
     */
    public function simpleQueryString($query, $fields = '_all', $defaultOperator = 'or', array $parameters = []);

    // -------------------------------------------------------------------------------
    // Term level queries
    // -------------------------------------------------------------------------------

    /**
     * Crée une requête qui retourne les documents ayant l'un des termes indiqués dans le champ passé en paramètre.
     *
     * @param string|string[] $query Le ou les termes recherchés.
     * @param string $field Le champ sur lequel porte la recherche.
     * @param array $parameters Paramètres additionnels de la requête.
     *
     * @return array Un tableau décrivant une requête de type "Term" (si vous passez un terme unique) ou une
     * requête de type "Terms" (si vous passez un tableau de termes).
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-term-query.html
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-terms-query.html
     */
    public function term($query, $field = 'all', array $parameters = []);

    /**
     * Alias de term().
     *
     * @param string|string[] $query Le ou les termes recherchés.
     * @param string $field Le champ sur lequel porte la recherche.
     * @param array $parameters Paramètres additionnels de la requête.
     *
     * @return array Un tableau décrivant une requête de type "Term" (si vous passez un terme unique) ou une
     * requête de type "Terms" (si vous passez un tableau de termes).
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-term-query.html
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-terms-query.html
     */
    public function terms($query, $field = 'all', array $parameters = []);

    /**
     * Crée une requête qui retourne les documents qui sont dans un intervalle donné.
     *
     * @param array $query Un tableau décrivant l'intervalle (par exemple : ['gte' => 10, 'lte' => 20]).
     * @param string $field Le champ sur lequel porte la recherche.
     * @param array $parameters Paramètres additionnels de la requête.
     *
     * @return array Un tableau décrivant une requête de type "Range".
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-range-query.html
     *
     * @throws InvalidArgumentException Si le tableau range comporte des paramètres invalides.
     */
    public function range(array $query, $field = '_all', array $parameters = []);

    /**
     * Crée une requête qui retourne les documents ayant au moins une valeur non nulle dans le champ indiqué.
     *
     * @param string $field Le champ sur lequel porte la recherche.
     * @param array $parameters Paramètres additionnels de la requête.
     *
     * @return array Un tableau décrivant une requête de type "Exists".
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-exists-query.html
     */
    public function exists($field, array $parameters = []);

    /**
     * Crée une requête qui retourne les documents pour lesquels le champ indiqué est vide.
     *
     * @param string $field Le champ sur lequel porte la recherche.
     * @param array $parameters Paramètres additionnels de la requête.
     *
     * @return array Un tableau décrivant une requête de type "Bool" contenant une clause "must-not(exists)" .
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-exists-query.html
     */
    public function missing($field, array $parameters = []);

    /**
     * Crée une requête qui retourne les documents ayant un terme commençant par le préfixe indiqué.
     *
     * @param string $query Le préfixe recherché.
     * @param string $field Le champ sur lequel porte la recherche.
     * @param array $parameters Paramètres additionnels de la requête.
     *
     * @return array Un tableau décrivant une requête de type "Prefix".
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-prefix-query.html
     */
    public function prefix($query, $field = '_all', array $parameters = []);

    /**
     * Crée une requête qui retourne les documents ayant un terme correspondant au masque indiqué.
     *
     * @param string $query L'expression recherchée (par exemple 'd?ocal*').
     * @param string $field Le champ sur lequel porte la recherche.
     * @param array $parameters Paramètres additionnels de la requête.
     *
     * @return array Un tableau décrivant une requête de type "Wildcard".
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-wildcard-query.html
     */
    public function wildcard($query, $field = '_all', array $parameters = []);

    // public function regexp();
    // public function fuzzy(); // deprecated in 5.0

    /**
     * Crée une requête qui retourne les documents ayant type elasticsearch indiqué.
     *
     * @param string $query Le nom du type elasticsearch (exemples : 'post', 'page', 'dbprisme-article'...)
     * @param array $parameters Paramètres additionnels de la requête.
     *
     * @return array Un tableau décrivant une requête de type "Type".
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-type-query.html
     */
    public function type($query, array $parameters = []);

    /**
     * Crée une requête qui retourne les documents ayant l'un des ID indiqués.
     *
     * @param scalar|array $id Les ID recherchés.
     * @param string $type Optionnel, le type elasticsearch.
     * @param array $parameters Paramètres additionnels de la requête.
     *
     * @return array Un tableau décrivant une requête de type "Ids".
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-ids-query.html
     */
    public function ids($id, $type = null, array $parameters = []);

    // -------------------------------------------------------------------------------
    // Compound queries
    // -------------------------------------------------------------------------------

    // public function constantScore(array $query, $boost = null);

    /**
     * Crée une requête qui retourne les documents qui satisfont les conditions booléennes passées en paramètres.
     *
     * @param string $field Le champ sur lequel porte la recherche.
     * @param array $clauses Un tableau de clauses booléennes. Chaque clause doit être un tableau contenant
     * une seule entrée dont la clé indique le type de clause ('must', 'filter', 'should', 'must_not').
     * @param array $parameters Paramètres additionnels de la requête.
     *
     * @return array Un tableau décrivant une requête de type "Bool".
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.0/query-dsl-bool-query.html
     *
     * @throws InvalidArgumentException Si le tableau comporte des clauses invalides.
     */
    public function bool(array $clauses, array $parameters = []);


    // -------------------------------------------------------------------------------
    // Clauses booléennes pour la méthode bool()
    // -------------------------------------------------------------------------------

    /**
     * Crée une clause "must" destinée à une requête bool.
     *
     * @param array $clause La requête à intégrer dans la clause.
     *
     * @return array Un tableau décrivant la clause.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.0/query-dsl-bool-query.html
     */
    public function must(array $clause);

    /**
     * Crée une clause "filter" destinée à une requête bool.
     *
     * @param array $clause La requête à intégrer dans la clause.
     *
     * @return array Un tableau décrivant la clause.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.0/query-dsl-bool-query.html
     */
    public function filter(array $clause);

    /**
     * Crée une clause "should" destinée à une requête bool.
     *
     * @param array $clause La requête à intégrer dans la clause.
     *
     * @return array Un tableau décrivant la clause.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.0/query-dsl-bool-query.html
     */
    public function should($clause);

    /**
     * Crée une clause "must" destinée à une requête bool.
     *
     * @param array $clause La requête à intégrer dans la clause.
     *
     * @return array Un tableau décrivant la clause.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.0/query-dsl-bool-query.html
     */
    public function mustNot($clause);

    // -------------------------------------------------------------------------------
    // Autres compound queries
    // -------------------------------------------------------------------------------

    // public function dismax();
    // public function functionScore();
    // public function boosting();
    // public function indices();


    // -------------------------------------------------------------------------------
    // Joining queries
    // -------------------------------------------------------------------------------

    // public function nested();
    // public function hasChild();
    // public function hasParent();
    // public function parentId();


    // -------------------------------------------------------------------------------
    // Geo queries
    // -------------------------------------------------------------------------------

    // public function geoShape();
    // public function geoBoundingBox();
    // public function geoDistance();
    // public function geoDistanceRange();
    // public function geoPolygon();
    // public function geoHashCell();


    // -------------------------------------------------------------------------------
    // Specialized queries
    // -------------------------------------------------------------------------------

    // public function moreLikeThis();
    // public function template();
    // public function script();


    // -------------------------------------------------------------------------------
    // Span queries
    // -------------------------------------------------------------------------------

    // todo ?
}
