<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2012, 2013 Daniel Ménard
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

use Docalist\QueryString;
use WP_Query;
use InvalidArgumentException, RuntimeException;

/**
 * La classe qui gère les recherches.
 */
class Searcher {

    /**
     * Le client utilisé pour communiquer avec le serveur ElasticSearch
     * (passé en paramètre au constructeur).
     *
     * @var ElasticSearchClient
     */
    protected $elasticSearchClient;

    /**
     * La configuration du moteur de recherche
     * (passée en paramètre au constructeur).
     *
     * @var Settings
     */
    protected $settings;

    /**
     * @var SearchRequest la requête adressée à ElasticSearch
     */
    protected $request;

    /**
     * Les résultats de la recherche.
     *
     * @var Results
     */
    protected $results;

    /**
     * Construit le moteur de recherche.
     *
     * @param ElasticSearchClient $elasticSearchClient
     * @param Settings $settings
     */
    public function __construct(ElasticSearchClient $elasticSearchClient, Settings $settings) {
        $this->elasticSearchClient = $elasticSearchClient;
        $this->settings = $settings;

        // @todo : revoir quelle est la priorité la plus adaptée pour les filtres
        add_filter('parse_query', array($this, 'onParseQuery'), 10, 1);

        // Permet aux autres de récupérer l'objet SearchRequest en cours
        add_filter('docalist_search_get_request', array($this, 'request'), 10, 0);

        // Permet aux autres de récupérer l'objet Results en cours
        add_filter('docalist_search_get_results', array($this, 'results'), 10, 0);

        add_filter('docalist_search_get_rank', array($this, 'rank'), 10, 1);

        add_filter('docalist_search_get_hit_link', array($this, 'hitLink'), 10, 1);
    }

    /**
     * Retourne la requête en cours.
     *
     * @return SearchRequest
     */
    public function request() {
        return $this->request;
    }

    /**
     * Retourne les résultats de la requête en cours.
     *
     * @return Results|null l'objet Results ou null si on n'a pas de requête en
     * cours.
     */
    public function results() {
        return $this->results;
    }

    /**
     * Retourne le rank d'un hit, c'est à dire la position de ce hit (1-based)
     * dans l'ensemble des réponses qui répondent à la requête.
     *
     * @param int $id
     *
     * @return int Retourne la position du hit dans les résultats (le premier
     * est à la position 1) ou zéro si l'id indiqué ne figure pas dans la liste
     * des réponses.
     */
    public function rank($id) {
        if ($this->results) {
            return $this->results->position($id) + 1 + ($this->request->page() - 1) * $this->request->size();
        }

        // Le hit demandé ne fait pas partie des réponses
        return 0; // // @todo null ? zéro ? exception ?
    }

    /**
     * Retourne le lien à utiliser pour afficher le hit indiqué tout seul sur
     * une page (i.e. recherche en format long).
     *
     * Le lien retourné est un lien qui permet de relancer une recherche avec
     * start=rank(id) et size=1
     *
     * @param int $id
     */
    public function hitLink($id) {
        // @formatter:off
        return QueryString::fromCurrent()
            ->set('page', $this->rank($id))
            ->set('size', 1)
            ->encode();
        // @formatter:on
    }

    /**
     * Filtre "parse_query" exécuté lorsque WordPress analyse la requête
     * adressée au site.
     *
     * On remplace la recherche standard de WordPress par notre moteur.
     *
     * WordPress considère que la requête est une recherche seulement si "s"
     * figure en query string ET qu'il est rempli. Dans notre cas, on considère
     * qu'il s'agit d'une recherche même si s est vide (dans ce cas une
     * recherche "*" est exécutée).
     *
     * Si la requête est une recherche, et qu'il s'agit de la requête
     * principale, on installe less filtres supplémentaires qui vont permettre
     * d'exécuter la recherche (onPostsRequest, onPostsResults, etc.)
     *
     * @param WP_Query $query La requête analysée par WordPress.
     *
     * @return WP_Query La requête, éventuellement modifiée.
     */
    public function onParseQuery(WP_Query & $query) {
        // Si c'est une sous-requête (query_posts, etc.) on ne fait rien
        if (! $query->is_main_query()) return $query;

        // Si ce n'est pas une recherche, on ne fait rien
        if (! array_key_exists('s', $_GET)) return $query;

        // Indique à WordPress que c'est une recherche (même si s est vide)
        $query->is_search = true;

        // Fournit à WordPress la requête SQL à exécuter (les ID retournés par ES)
        add_filter('posts_request', array($this, 'onPostsRequest'), 10, 2);

        // Indique à WordPress le nombre de réponses obtenues
        add_filter('posts_results', array($this, 'onPostsResults'), 10, 2);

        return $query;
    }

    /**
     * Filtre "post_request" exécuté lorsque WordPress construit la requête
     * SQL à exécuter pour établir la liste des posts à afficher.
     *
     * Ce filtre n'est exécuté que si onParseQuery() a déterminé qu'il
     * s'agissait d'une recherche.
     *
     * On intercepte la recherche standard de WordPress, on lance une requête
     * Elastic Search et on récupère les IDs des hits obtenus.
     *
     * On retourne ensuite à WordPress une requête SQL qui permet de charger
     * les posts correspondants aux hits tout en maintenant le tri (par
     * pertinence, par exemple) établi par le moteur de recherche :
     *
     * <code>
     * SELECT * FROM wp_posts WHERE ID in (<IDs>) ORDER BY FIELD(id, <IDs>)
     * <code>
     *
     * Si la recherche ElasticSearch est infructueuse (aucune réponse, équation
     * erronnée, serveur qui ne répond pas, etc.) la méthode retourne null.
     * Dans ce cas, WordPress ne va exécuter aucune requête sql (cf. le code
     * source de WPDB::get_results()) et va afficher la page "aucune réponse".
     *
     * @param string $sql La requête SQL initiale construite par WordPress.
     *
     * @param WP_Query $query L'objet Query construit par WordPress.
     *
     * @return string|null Retourne la requête sql à exécuter : soit la requête
     * d'origine, soit la requête permettant de charger les réponses retournées
     * par ElasticSearch.
     */
    public function onPostsRequest($sql, WP_Query & $query) {
        /* @var $wpdb Wpdb */
        global $wpdb;

        // Empêche WordPress de faire ensuite une requête "SELECT FOUND_ROWS()"
        // C'est inutile car onPostResults va retourner directement le nombre de réponses
        $query->query_vars['no_found_rows'] = true;

        // Construit la requête qu'on va envoyer à ElasticSearch
        $args = QueryString::fromCurrent();
        $this->request = new SearchRequest($this->elasticSearchClient, $args);

        // Synchronize size et posts_per_page pour que le pager fonctionne
        $size = $this->request->size();
        if ($size !== $query->get('posts_per_page')) {
            $query->set('posts_per_page', $size);
        }

        // Synchronize page et paged pour que le pager fonctionne
        $page = $this->request->page();
        if ($page !== $query->get('paged')) {
            $query->set('paged', $page);
        }

        // Exécute la recherche
        try {
            $this->results = $this->request->execute();
        } catch (Exception $e) {
            return null;
        }

        // Récupère les hits obtenus
        $hits = $this->results->hits();

        // Aucune réponse : retourne sql=null pour que wpdb::query() ne fasse aucune requête
        if (empty($hits)) {
            return null;
        }

        // Construit la liste des ID des réponses obtenues
        $id = array();
        foreach($hits as $hit) {
            $id[] = $hit->_id;
        }

        // Construit une requête sql qui récupére les posts dans l'ordre
        // indiqué par ElasticSearch (http://stackoverflow.com/a/3799966)
        $sql = 'SELECT * FROM %s WHERE ID in (%s) ORDER BY FIELD(id,%2$s)';
        $sql = sprintf($sql, $wpdb->posts, implode(',', $id));

        return $sql;
    }


    /**
     * Filtre "post_results" appelé lorsque la requête SQL générée a été
     * exécutée.
     *
     * Ce filtre n'est exécuté que si onParseQuery() a déterminé qu'il
     * s'agissait d'une recherche.
     *
     * On indique à WordPress le nombre exact de réponses obtenues (tel que
     * retourné par Elastic Search) et on calcule le nombre total de pages de
     * réponses possibles en intialisant les propriétés "found_posts" et
     * "max_num_pages" de l'objet Query passé en paramètre.
     *
     * @param array $posts La liste des posts obtenus lors de la recherche.
     *
     * @param WP_Query $query Les paramètres de la requête WordPress en cours.
     *
     * @return array $posts Retourne inchangé le tableau passé en paramètre
     * (seul $query nous intéresse).
     */
    public function onPostsResults(array $posts = null, WP_Query & $query) {
        $total = $this->results ? $this->results->total() : 0;
        $size = $this->request->size();

        $query->found_posts = $total;
        $query->max_num_pages = (int) ceil($total / $size);

        return $posts;
    }
}