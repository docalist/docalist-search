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
 */
namespace Docalist\Search\Indexer;

use Docalist\Search\IndexManager;
use Docalist\Search\ElasticSearchMappingBuilder;
use wpdb;
use WP_Post;
use Docalist\Search\SearchRequest;

/**
 * Un indexeur pour les articles de WordPress.
 */
class PostIndexer extends AbstractIndexer
{
    public function getType()
    {
        return 'post';
    }

    public function getLabel()
    {
        return get_post_type_object($this->getType())->labels->name;
    }

    public function getCategory()
    {
        return __('Contenus WordPress', 'docalist-search');
    }

    public function getCollection()
    {
        return 'posts';
    }

    public function buildIndexSettings(array $settings)
    {
        $mapping = new ElasticSearchMappingBuilder('fr-text'); // todo : rendre configurable

        $mapping->addField('in')->keyword();
        $mapping->addField('type')->keyword();
        $mapping->addField('status')->keyword();
        $mapping->addField('slug')->text();
        $mapping->addField('createdby')->keyword();
        $mapping->addField('creation')->dateTime();
        $mapping->addField('lastupdate')->dateTime();
        $mapping->addField('title')->text();
        $mapping->addField('content')->text();
        $mapping->addField('excerpt')->text();
        if (is_post_type_hierarchical($this->getType())) {
            $mapping->addField('parent')->integer();
        }

        $settings['mappings'][$this->getType()] = $mapping->getMapping();

        return $settings;
    }

    /**
     * Retourne la liste des status à indexer.
     *
     * @return string
     */
    protected function getStatuses()
    {
        return ['publish', 'pending', 'private'];
    }

    public function activateRealtime(IndexManager $indexManager)
    {
        $statuses = array_flip($this->getStatuses());
        $type = $this->getType();

        add_action('transition_post_status',
            function ($newStatus, $oldStatus, WP_Post $post) use ($indexManager, $type, $statuses) {
                // Si ce n'est pas un de nos contenus, terminé
                if ($post->post_type !== $type) {
                    return;
                }

                // Si le nouveau statut est indexable, on indexe le post
                if (isset($statuses[$newStatus])) {
                    return $this->index($post, $indexManager);
                }

                // Si le nouveau statut n'est pas indexable mais que l'ancien l'était, on désindexe le post
                if (isset($statuses[$oldStatus])) {
                    return $this->remove($post, $indexManager);
                }
            },
            10, 3
        );

        add_action('deleted_post',
            function ($id) use ($indexManager) {
                $post = get_post($id);
                if ($post->post_type === $this->getType()) {
                    return $this->remove($post, $indexManager);
                }
            }
        );
    }

    public function indexAll(IndexManager $indexManager)
    {
        $wpdb = docalist('wordpress-database'); /* @var wpdb $wpdb */
        $offset = 0;
        $limit = 1000;

        // Prépare la requête utilisée pour charger les posts par lots de $limit
        $sql = sprintf(
            "SELECT * FROM %s WHERE post_type='%s' AND post_status IN ('%s') ORDER BY ID ASC LIMIT %%d OFFSET %%d",
            $wpdb->posts,
            $this->getType(),
            implode("','", $this->getStatuses())
        );

        // remarque : pas besoin d'appeler prepare(). Un post_type ou un statut ne contiennent que des lettres et
        // on contrôle les deux autres entiers passés en paramètre.

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
            foreach ($posts as $post) {
                $this->index($post, $indexManager);
            }

            // Passe au lot suivant
            $offset += count($posts);
        }
    }

    protected function getID($post) /* @var $post WP_Post */
    {
        return $post->ID;
    }

    protected function map($post) /* @var $post WP_Post */
    {
        $document = [];

        // Nom de la collection (in)
        $document['in'] = $this->getCollection();

        // Type
        $document['type'] = $this->getType();

        // Statut
        $document['status'] = $post->post_status;
//      $status = get_post_status_object($post->post_status);
//      $document['status'] = $status ? $status->label : $post->post_status;

        // Slug
        $document['slug'] = $post->post_name;

        // CreatedBy
        $user = get_user_by('id', $post->post_author);
        $document['createdby'] = $user ? $user->user_login : $post->post_author;

        // Date de création
        $document['creation'] = $post->post_date;

        // Date de modification
        $document['lastupdate'] = $post->post_modified;

        // Titre
        $document['title'] = $post->post_title;

        // Extrait
        if (! empty($post->post_excerpt)) {
            $document['excerpt'] = $post->post_excerpt;
        }

        // Contenu
        if (! empty($post->post_content)) {
            $document['content'] = $post->post_content;
        }

        // Parent
        if (is_post_type_hierarchical($this->getType()) && ! empty($post->post_parent)) {
            $document['parent'] = (int) $post->post_parent;
        }

        return $document;
    }

    protected function getStatusFilter()
    {
        // Détermine le nom de la capacité "read_private_posts" pour ce type
        $postType = get_post_type_object($this->getType());
        $readPrivatePosts = $postType->cap->read_private_posts;

        // Si l'utilisateur en cours a le droit "read_private_posts", inutile de filtrer par statut
        if (current_user_can($readPrivatePosts)) {
            return null;
        }

        // Détermine la liste des statuts publics
        $public = [];
        foreach ($this->getStatuses() as $status) {
            $statusObject = get_post_status_object($status);
            $statusObject && $statusObject->public && $public[] = $status;
        }

        // L'utilisateur ne peut voir que les posts publics
        $filter = SearchRequest::termFilter('status', $public);

        //  Et ceux dont il est auteur
        if (is_user_logged_in()) {
            $user = wp_get_current_user()->user_login;

            $filter = SearchRequest::shouldFilter(
                $filter,
                SearchRequest::termFilter('createdby', $user)
            );
        }

        // Ok
        return $filter;
    }

    public function getSearchFilter()
    {
        $typeFilter = parent::getSearchFilter();
        $statusFilter = $this->getStatusFilter();
        if (empty($statusFilter)) {
            return $typeFilter;
        }

        return SearchRequest::mustFilter($typeFilter, $statusFilter);
    }
}
