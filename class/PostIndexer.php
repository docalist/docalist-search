<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
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

use WP_Post;

/**
 * Un indexeur pour les articles de WordPress.
 */
class PostIndexer extends TypeIndexer {
    /**
     * Construit l'indexeur.
     *
     * @param string $type Optionnel, le type de contenu géré par cet indexeur
     * ("post" par défaut, mais les classes descendantes peuvent ainsi passer
     * un autre type).
     */
    public function __construct($type = 'post') {
        parent::__construct($type);
    }

    /**
     * Retourne la liste des status à indexer.
     *
     * @return string
     */
    public function statuses() {
        return ['publish', 'pending', 'private'];
    }

    public function contentId($post) { /* @var $post WP_Post */
        return $post->ID;
    }

    public function mapping() {
        $mapping = new MappingBuilder('fr-text'); // todo : rendre configurable

        foreach(self::$stdFields as $field) {
            static::standardMapping($field, $mapping);
        }

        return $mapping->mapping();
    }


    public function map($post) { /* @var $post WP_Post */
        $document = [];
        foreach(self::$stdFields as $field) {
            $value = $post->$field;
            $value && static::standardMap($field, $value, $document);
        }

        return $document;
    }

    /**
     * Réindexe tous les documents de ce type.
     *
     * @param Indexer $indexer
     */
    public function indexAll(Indexer $indexer) {
        global $wpdb;

        $offset = 0;
        $limit = 1000;

        // Prépare la requête utilisée pour charger les posts par lots de $limit
        $sql = sprintf(
           'SELECT * FROM %s '
         . "WHERE post_type = '%s' AND post_status IN ('%s') "
         . 'ORDER BY ID ASC '
         . 'LIMIT %s OFFSET %s',

            $wpdb->posts,
            $this->type(),
            implode("','", $this->statuses()),
            '%d',
            '%d'
        );

        // remarque : pas besoin d'appeler prepare(). Un post_type ou un
        // statut ne contiennent que des lettres et on contrôle les deux autres
        // entiers passés en paramètre.

        for (;;) {
            // Prépare la requête pour le prochain lot
            $query = sprintf($sql, $limit, $offset);

            // $output == OBJECT (par défaut) est le plus efficace, pas de recopie
            $posts = $wpdb->get_results($query);

            // Si le lot est vide, c'est qu'on a terminé
            if (empty($posts)) {
                break;
            }

            // Indexe tous les posts de ce lot
            foreach($posts as $post) {
                $this->index($post, $indexer);
            }

            // Passe au lot suivant
            $offset += count($posts);

            // La ligne (commentée) suivante est pratique pour les tests
            // if ($offset >= 1000) break;
        }
    }
/*
    public function OLDindexAll(Indexer $indexer) {
        $offset = 0;
        $size = 1000;

        $query = new WP_Query();

        $args = [
            'post_type' => $this->type(),
            'post_status' => $this->statuses(),

            'offset' => $offset,
            'posts_per_page'=> $size,

            'orderby' => 'ID',
            'order' => 'ASC',

            'cache_results' => false,
            'update_post_term_cache' => false,
            'update_post_meta_cache' => false,

            'no_found_rows' => true
        ];

        while ($posts = $query->query($args)) {
            echo '<pre>', $query->request, '</pre>';
            foreach($posts as $post) {
                $this->index($post, $indexer);
            }
            $args['offset'] += count($posts);
        }
    }
*/

    public function realtime() {
        add_action('transition_post_status', function($newStatus, $oldStatus, WP_Post $post) {
            $this->onStatusChange($newStatus, $oldStatus, $post);
        }, 10, 3);

        add_action('delete_post', function($id) {
            $this->onDelete($id);
        });
    }

    /**
     * Ajoute, modifie ou supprime un post de l'index lorsque son statut change.
     *
     * @param string $newStatus
     * @param string $oldStatus
     * @param WP_Post $post
     */
    protected function onStatusChange($newStatus, $oldStatus, WP_Post $post) {
        static $statuses = null;

        if ($post->post_type !== $this->type) {
            return;
        }

        $this->log && $this->log->debug('Status change for {type}#{ID}: {old}->{new}', [
            'type' => $this->type,
            'ID' => $post->ID,
            'old' => $oldStatus,
            'new' => $newStatus
        ]);

        if (is_null($statuses)) {
            $statuses = array_flip($this->statuses());
        }

        /* @var $indexer Indexer */
        $indexer = docalist('docalist-search-indexer');

        // Si le nouveau statut est indexable, on indexe le post
        if (isset($statuses[$newStatus])) {
            $this->index($post, $indexer);
        }

        // Le nouveau statut n'est pas indexé, si l'ancien l'était, on l'enlève
        elseif (isset($statuses[$oldStatus])) {
            $indexer->delete($this->type, $post->ID);
        }
    }

    /**
     * Enlève un document de l'index quand il est supprimé.
     *
     * @param int $id
     */
    public function onDelete($id) {
        $post = get_post($id);

        if ($post->post_type !== $this->type) {
            return;
        }

        $this->log && $this->log->debug('Deleted {type}#{ID}', [
            'type' => $this->type,
            'ID' => $id
        ]);

        /* @var $indexer Indexer */
        $indexer = docalist('docalist-search-indexer');
        $indexer->delete($this->type, $post->ID);
    }
}