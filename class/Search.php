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
use Docalist\Plugin;
use StdClass, Exception;
use WP_Query;

/**
 * Plugin elastic-search.
 */
class Search extends Plugin {

    /**
     * @var object la requête adressée à ElasticSearch
     */
    protected $request;

    /**
     * @var object la réponse de ElasticSearch
     */
    protected $response;

    /**
     * @inheritdoc
     */
    public function register() {
        // Configuration du plugin
        $this->add(new Settings);

        // Client ElasticSearch
        $this->add(new ElasticSearch);

        // Back office
        add_action('admin_menu', function() {
            // Configuration
            $this->add(new SettingsPage);

            // Outils
            $this->add(new Tools);
        });

        // Si l'utilisateur n'a pas encore activé la recherche, terminé
        if (! $this->setting('general.enabled')) {
            return;
        }

        add_filter('posts_request', function($sql, WP_Query &$query){
            return $this->search($sql, $query);
        }, 10, 2 );

        add_filter('posts_results', function(array $posts = null, WP_Query &$query){
            return $this->searchResults($posts, $query);
        }, 10, 2 );

        add_action('widgets_init', function() {
            register_widget( __NAMESPACE__ . '\FacetsWidget' );
        });
    }

    private function searchResults(array $posts = null, WP_Query &$query) {
        if ($this->response) {
            $total = $this->response->hits->total;
            $size = $this->request->size;

            $query->found_posts = $total;
            $query->max_num_pages = ceil($total / $size);
            //$query->query_vars['paged'] = (int) ($this->request->from / $size);
        }

        return $posts;
    }

    private function search($sql, WP_Query &$q) {
        global $wpdb;

        $this->request = $this->response = null;
        if (!$q->is_main_query() || !$q->is_search()){
//            echo 'NOT SEARCH or NOT MAIN QUERY<br />';
            return $sql;
        }

        $qv = (object) $q->query_vars;
/*
        echo "SEARCH<br />";

        echo '<pre>';
        var_export($q);
        echo '</pre>';
*/
        // Empêche wp de faire ensuite une requête "SELECT FOUND_ROWS()"
        $q->query_vars['no_found_rows'] = true;

        // Construit la requête qu'on va envoyer à ElasticSearch
        $this->request = new StdClass;
        $this->request->size = isset($qv->posts_per_page) ? $qv->posts_per_page : 15; //TODO: option
        $page = (isset($qv->paged) && $qv->paged > 0) ? $qv->paged-1 : 0;
        $this->request->from = $page * $this->request->size;
/*
        $this->request->query = array(
            'query_string' => array(
                'query' => $qv->s,
            )
        );
 */
/*
        $this->request->query = array(
            'multi_match' => array(
                'query' => $qv->s,
                'fields' => array('title^2', 'othertitle', 'topics.terms'),
            )
        );
*/
        $this->request->query = array(
            'query_string' => array(
                'query' => $qv->s,
                'fields' => array('title^2', 'othertitle', 'topic.term^5'),
                'use_dis_max' => true,
            )
        );

        $this->request->fields = array('ids');

        // Interroge ElasticSearch
        try {
            $this->response = $this->get('elasticsearch')->get('_search', $this->request);
        } catch (Exception $e) {
            return null;
        }
        // TODO: erreur, ne répond pas : return $sql

        // Aucune réponse : retourne sql=null, wpdb::query() ne fera aucune requête
        if ($this->response->hits->total === 0) {
            return null;
        }

        // Construit la liste des ID des réponses obtenues
        $id = array();
        foreach($this->response->hits->hits as $hit) {
            $id[] = $hit->_id;
        }

        // Constuit une requête sql pour de récupérer les posts dans l'ordre
        // indiqué par ElasticSearch (http://stackoverflow.com/a/3799966)
        $sql = 'SELECT * FROM %s WHERE ID in (%s) ORDER BY FIELD(id,%2$s)';
        $sql = sprintf($sql, $wpdb->posts, implode(',', $id));

        return $sql;
    }

    public function request() {
        return $this->request;
    }

    public function explain() {
        // http://www.elasticsearch.org/guide/reference/api/validate.html
        if (is_null($this->request)) {
            return 'Aucune requête en cours';
        }

        $response = $this->get('elasticsearch')->get('_validate/query?explain', $this->request->query);
        return $response->explanations[0];
    }

    public function explainHit($id) {
        // http://www.elasticsearch.org/guide/reference/api/validate.html
        if (is_null($this->request)) {
            return 'Aucune requête en cours';
        }

        // TODO: dclref fixé en dur
        $response = $this->get('elasticsearch')->get("dclref/$id/_explain", $this->request);
        return $response;
    }

    public function facets(array $facets) {
        if (is_null($this->request)) {
            return 'Aucune requête en cours';
        }

        // Construit la requête ES
        $request = array(
            'query' => $this->request->query,
            'size' => 0,
            'facets' => array(),
        );

        foreach($facets as $name => $facet) {
            $type = isset($facet['type']) ? $facet['type'] : 'terms';
            unset($facet['type']);
            $request['facets'][$name] = array(
                $type => $facet,
            );
        }

        // Interroge ElasticSearch
        $response = $this->get('elasticsearch')->post('_search', $request);

        return $response->facets;
    }
}
