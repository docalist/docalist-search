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

use WP_Query, WP_Post;

/**
 * Une classe qui permet d'indexer les articles de WordPress
 */
class PostIndexer {
    /**
     * Construit un nouvel indexeur.
     */
    public function __construct() {
        // Cette classe sait indexer les articles et les pages
        add_filter('docalist_search_get_types', function ($types) {
            $types['post'] = get_post_type_object('post')->labels->name;
            $types['page'] = get_post_type_object('page')->labels->name;

            return $types;
        });

        // Fonction appellée pour réindexer tous les articles
        add_action('docalist_search_reindex_post', function(){
            $this->reindex('post');
        });

        // Fonction appellée pour réindexer toutes les pages
        add_action('docalist_search_reindex_page', function(){
            $this->reindex('page');
        });
    }

    protected function reindex($type) {
        $offset = 0;
        $size = 1000;

        $query = new WP_Query();

        $args = array(
            'post_type' => $type,
            'post_status' => 'publish',

            'offset' => $offset,
            'posts_per_page'=> $size,

            'orderby' => 'ID',
            'order' => 'ASC',

            'cache_results' => false,
            'update_post_term_cache' => false,
            'update_post_meta_cache' => false,

            'no_found_rows' => true
        );

        while ($posts = $query->query($args)) {
            //echo "Query exécutée avec offset=", $args['offset'], ', result=', count($posts), '<br />';
            foreach($posts as $post) {
                do_action('docalist_search_index', $type, $post->ID, $this->map($post));
            }
            $args['offset'] += count($posts);
            break;
        }
    }

    protected function map(WP_Post $post) {
        $document = array(
            'title' => $post->post_title,
            'date' => $post->post_date,
            'content' => $post->post_content,
            'status' => $post->post_status,
        );

        return $document;
    }
}