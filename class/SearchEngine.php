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
use Docalist\Http\JsonResponse;
use Exception;

/**
 * La classe qui gère les recherches.
 */
class SearchEngine {

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
     * @var SearchResults
     */
    protected $results;

    /**
     * Construit le moteur de recherche.
     *
     * @param Settings $settings
     */
    public function __construct(Settings $settings) {
        $this->settings = $settings;

        add_filter('query_vars', function($vars) {
            $vars[] = 'docalist-search';
            $vars[] = 'q';

            return $vars;
        });

        // Supprime les "search rewrite rules" par défaut de wordpress
        add_filter('search_rewrite_rules', function(array $rules) {

            return [];

            // remarque : on ne peut pas créer les nouvelles règles içi car les
            // "search rules" ne sont pas prioritaires (par exemple la rule
            // "/prisme/search" ne lancera pas une recherche, elle esaaiera
            // d'afficher la notice qui a le permalien "search" (et donc 404)
            // Du coup, on supprime les règles ici, et on crée les nouvelles
            // dans le filtre ci-dessous, en les insérant au tout début des
            // rewrite rules.

        });

        // Crée nos propres "search rewrite rules" et permet aux plugins d'en
        // créer de nouvelles. Les routes créées sont prioritaires sur toutes
        // les autres (on les insère en tout début du tableau des routes wp)
        add_filter( 'rewrite_rules_array', function(array $rules) {
            $new = [ 'search' => 'index.php?docalist-search=1' ];
            $new = apply_filters('docalist_search_get_rewrite_rules', $new);

            return $new + $rules;
        });

        // @todo : revoir quelle est la priorité la plus adaptée pour les filtres
        add_filter('parse_query', array($this, 'onParseQuery'), 10, 1);

        // Permet aux autres de récupérer l'objet SearchRequest en cours
        add_filter('docalist_search_get_request', array($this, 'request'), 10, 0);

        // Permet aux autres de récupérer l'objet SearchResults en cours
        add_filter('docalist_search_get_results', array($this, 'results'), 10, 0);

        add_filter('docalist_search_get_rank', array($this, 'rank'), 10, 1);

        add_filter('docalist_search_get_hit_link', array($this, 'hitLink'), 10, 1);

        add_filter('get_search_query', function($s) {
            return $this->request ? $this->request->asEquation() : $s;
        } );
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
     * @return SearchResults|null l'objet Results ou null si on n'a pas de
     * requête en cours.
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
     * Remplace la recherche standard de WordPress par notre moteur.
     *
     * Si la requête est une recherche, et qu'il s'agit de la requête
     * principale, on installe les filtres supplémentaires qui vont permettre
     * d'exécuter la recherche (onPostsRequest, onPostsResults, etc.)
     *
     * @param WP_Query $query La requête analysée par WordPress.
     *
     * @return WP_Query La requête, éventuellement modifiée.
     */
    public function onParseQuery(WP_Query & $query) {
        // Si c'est une sous-requête (query_posts, etc.) on ne fait rien
        if (! $query->is_main_query()) {
            return $query;
        }

        // Si ce n'est pas une recherche, on ne fait rien
        if (! $this->isSearchQuery($query)) {
            return $query;
        }

        // Fournit à WordPress la requête SQL à exécuter (les ID retournés par ES)
        add_filter('posts_request', array($this, 'onPostsRequest'), 10, 2);

        // Indique à WordPress le nombre de réponses obtenues
        add_filter('posts_results', array($this, 'onPostsResults'), 10, 2);

        return $query;
    }

    /**
     * Détermine si la requête WordPress en cours est une recherche.
     *
     * WordPress considère que la requête en cours est une recherche dès lors
     * qu'on a un paramètre "s" non vide en query string.
     *
     * C'est génant car, la requête est exécutée avant même qu'on ait décidé
     * de ce qu'on voulait en faire. Dans notre cas, on peut avoir une
     * recherche en paramètre et vouloir :
     *
     * - afficher un formulaire de recherche avancée qui reprend les paramètres
     * - expliquer comment a été interprétée la recherche
     * - faire un export des réponses correspondant à cette recherche
     * - ajouter dans un panier
     * - faire un traitement en batch sur l'ensemble des réponses
     * - etc.
     *
     * La seule solution est de désactiver complètement la recherche wordpress
     * et de ne lancer une recherche que si on nous le demande explicitement.
     *
     * Pour cela, on introduit une nouvelle query_var : "docalist-search".
     * Cette query var doit être fournie en query string (peu courant) ou
     * initialisée via une rewrite rule (cas général).
     *
     * C'est ce que fait la rewrite rule que l'on crée (/search) : elle se
     * contente d'initialiser "docalist_search" à true.
     *
     * Au final, on considère que la requête en cours est une recherche si et
     * seulement si on docalist-search=true dans les query vars de WordPress.
     */
    protected function isSearchQuery(WP_Query $query) {
        $query->is_search = isset($query->query_vars['docalist-search']);

        return $query->is_search;
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
        //$args->set('q', $query->query_vars['q']); // cas où q est en qv mais pas en query string (e.g. init par une rewrite rule)
        if (! empty($query->query_vars['post_type']) && $query->query_vars['post_type'] !== 'any') {
            $args->add('_type', $query->query_vars['post_type']);
        }

        $this->request = new SearchRequest($args);

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

    /**
     * Recherche dans un champ tous les termes qui commencent par un préfixe
     * donné.
     *
     * @param string $source nom du champ index.
     * @param string $search préfixe recherché.
     *
     * @return string|array En cas de succès, retourne un tableau de termes.
     * Chaque terme est un objet contenant les clés "term" et "count". Par
     * exemple la recherche "NOT" sur un champ "auteur" pourrait retourner :
     * [
     *     {"term": "NOTAT (Nicole)", "count": 1 },
     *     {"term": "NOTHOMB (AMELIE)", "count": 3},
     * ]
     *
     * Si aucun terme ne commence par le préfixe indiqué, la méthode retourne
     * un tableau vide.
     */
    public function lookup($source, $search) {
        // Remarques :
        // 1. Pour le moment, le "completion suggester" ne permet pas de filtrer
        //    par type. Ce sera possible plus tard avec le "ContextSuggester".
        //    cf. https://github.com/elasticsearch/elasticsearch/issues/3958
        //    Les lookups faits sur un champ portent donc sur toutes les bases
        //    de données qui contiennent ce champ.
        // 2. Il n'est pas possible de lancer une recherche avec search='', ça
        //    retourne zéro réponses.
        // 3. Le mode "recherche par code" (avec search entre crochets) n'est
        //    pas supporté (les crochets sont supprimés).

        // On ne gère pas la recherche par code, ignore les crochets
        if (strlen($search) >= 2 && $search[0] === '[' && substr($search, -1) === ']') {
            $search = substr($search, 1, -1);
        }

        // Construit la requête Elastic Search
        // @see http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/search-suggesters-completion.html
        $query = [
            'lookup' => [
                'text' => $search,
                'completion' => [
                    'field' => $source,
                    'size' => 10,
                    // 'fuzzy' => true
                ]
            ]
        ];

        // Exécute la requête
        $result = docalist('elastic-search')->post('_suggest', $query);

        // Récupère les suggestions
        if (isset($result->lookup[0]->options)) {
            return $result->lookup[0]->options;
        } else {
            return array(); // erreur ?
        }
    }
}