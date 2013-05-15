<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2013 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */
namespace Docalist\Search;
use Docalist;
use StdClass, Exception;

/**
 * Le résultat d'une requête de recherche adressée à ElasticSearch.
 */
class Results {
    /**
     * La réponse brute retournée par ElasticSearch.
     *
     * @var StdClass
     */
    protected $response;

    /**
     * Initialise l'objet à partir de la réponse retournée par Elastic Search.
     *
     * @param StdClass $response
     */
    public function __construct(StdClass $response) {
        $this->response = $response;
    }

    /**
     * Retourne le temps mis pour exécuter la requête.
     *
     * @return int durée en millisecondes
     */
    public function took() {
        return $this->response->took;
    }

    /**
     * Indique si la requête a généré un time out.
     *
     * @return bool
     */
    public function timedOut() {
        return $this->response->timed_out;
    }

    /**
     * Informations sur les shards qui ont exécuté la requête.
     *
     * @return StdClass un objet contenant les propriétés :
     * - total
     * - successful
     * - failed
     */
    public function shards() {
        return $this->response->shards();
    }

    /**
     * Retourne le nombre total de réponses obtenues.
     *
     * @return int
     */
    public function total() {
        return $this->response->hits->total;
    }

    /**
     * Retourne le score maximal obtenu par la meilleure réponse.
     *
     * @return float
     */
    public function maxScore() {
        return $this->response->hits->max_score;
    }

    /**
     * Retourne la liste des réponses obtenues.
     *
     * @return array Chaque réponse est un objet contenant les propriétés
     * suivantes :
     *
     * _id : numéro de référence de l'enregistrement
     * _score : score obtenu
     * _index : nom de l'index ElasticSearch d'où provient le hit
     * _type : type du hit
     */
    public function hits() {
        return $this->response->hits->hits;
    }

    /**
     * Retourne la liste des facettes.
     *
     * @return array Chaque réponse est un objet contenant les propriétés
     * suivantes :
     *
     * _id : numéro de référence de l'enregistrement
     * _score : score obtenu
     * _index : nom de l'index ElasticSearch d'où provient le hit
     * _type : type du hit
     */
    public function facets() {
        return isset($this->response->facets) ? $this->response->facets : array();
    }

    /**
     * Indique si les résultats contiennent la facette dont le nom est indiqué.
     *
     * @param string $name
     */
    public function hasFacet($name) {
        return isset($this->response->facets->$name);
    }

    /**
     * Retourne la facette dont le nom est indiqué.
     *
     * @param string $name
     *
     * @return StdClass un objet contenant les clés :
     * _type
     * missing
     * total
     * other
     * terms : un tableau d'objets contenant term et count
     */
    public function facet($name) {
        return isset($this->response->facets->$name) ? $this->response->facets->$name : null;
    }

    /**
     * Retourne une explication technique indiquant la manière dont le score
     * a été calculé pour la réponse dont l'ID est passé en paramètre.
     *
     * La recherche doit avoir été lancée avec l'option "explain-hits" à true.
     *
     * @param int $id
     *
     * @return string|array
     *
     * La méthode retourne 'n/a' si l'explication n'est pas disponible pour le
     * hit demandé (explain-hits non activé)et elle retourne 'not a hit'
     * si l'id passé en paramètre ne figure pas dans la liste des réponses.
     *
     * Dans le cas contraire, elle retourne un tableau contenant l'explication
     * fournie par Elastic Search.
     *
     * @see http://www.elasticsearch.org/guide/reference/api/search/explain/
     */
    public function explainHit($id) {
        // Remarque : l'ID retourné par wordpress (get_the_id) est un entier
        // alors que pour ES les ID sont des chaines. Pour cette raison, on
        // utilise "==" plutôt qu'une égalité stricte dans le test ci-dessous.

        foreach ($this->response->hits->hits as $hit) {
            if ($hit->_id == $id) {
                return isset($hit->_explanation) ? $hit->_explanation : 'n/a';
            }
        }

        // Le hit demandé ne fait pas partie des réponses
        return 'not a hit';
    }
}