<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Search\Indexer;

use Docalist\Search\Indexer;
use Docalist\Search\Indexer\Field\CollectionIndexer;
use Docalist\Search\Indexer\Field\PostAuthorIndexer;
use Docalist\Search\Indexer\Field\PostContentIndexer;
use Docalist\Search\Indexer\Field\PostDateIndexer;
use Docalist\Search\Indexer\Field\PostExcerptIndexer;
use Docalist\Search\Indexer\Field\PostModifiedIndexer;
use Docalist\Search\Indexer\Field\PostParentIndexer;
use Docalist\Search\Indexer\Field\PostStatusIndexer;
use Docalist\Search\Indexer\Field\PostTitleIndexer;
use Docalist\Search\Indexer\Field\PostTypeIndexer;
use Docalist\Search\Indexer\Field\TaxonomyIndexer;
use Docalist\Search\IndexManager;
use Docalist\Search\Mapping;
use Docalist\Search\QueryDSL;
use Docalist\Tokenizer;
use InvalidArgumentException;
use WP_Post;
use WP_Taxonomy;
use WP_Term;
use wpdb;

/**
 * Classe de base pour les indexeurs qui gèrent des objets WP_Post (posts, pages, custom post types, etc.)
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
abstract class CustomPostTypeIndexer implements Indexer
{
    /**
     * {@inheritDoc}
     */
    public function getMapping(): Mapping
    {
        // Crée le mapping pour ce type de post
        $postType = $this->getType();
        $mapping = new Mapping($postType);

        // Indexe les champs de base obligatoires
        CollectionIndexer::buildMapping($mapping);
        PostAuthorIndexer::buildMapping($mapping);
        PostDateIndexer::buildMapping($mapping);
        PostModifiedIndexer::buildMapping($mapping);
        PostStatusIndexer::buildMapping($mapping);
        PostTitleIndexer::buildMapping($mapping);
        PostTypeIndexer::buildMapping($mapping);

        // Indexe le champ post_content seulement si le post type le supporte
        if (post_type_supports($postType, 'editor')) {
            PostContentIndexer::buildMapping($mapping);
        }

        // Indexe le champ post_excerpt seulement si le post type le supporte
        if (post_type_supports($postType, 'excerpt')) {
            PostExcerptIndexer::buildMapping($mapping);
        }

        // Indexe le champ post_parent seulement si le type de post est hiérarchique
        if (is_post_type_hierarchical($this->getType())) {
            PostParentIndexer::buildMapping($mapping);
        }

        // Crée le mapping des taxonomies indexées
        foreach ($this->getIndexedTaxonomies() as $taxonomy) {
            TaxonomyIndexer::buildMapping($taxonomy, $mapping);
        }

        // Ok
        return $mapping;
    }

    /**
     * Génère les données à indexer pour le post passé en paramètre.
     *
     * @param WP_Post $post Post à indexer
     *
     * @return array Données à ajouter à l'index.
     */
    public function getIndexData(WP_Post $post): array
    {
        // Cache des taxonomies indexées
        static $taxonomies = null; // différent pour chaque classe, commun pour toutes les instances d'une classe

        // Indexe les champs de base obligatoires
        $data = [];
        CollectionIndexer::buildIndexData($this->getCollection(), $data);
        PostAuthorIndexer::buildIndexData((int) $post->post_author, $data);
        PostDateIndexer::buildIndexData($post->post_date, $data);
        PostModifiedIndexer::buildIndexData($post->post_modified, $data);
        PostStatusIndexer::buildIndexData($post->post_status, $data);
        PostTitleIndexer::buildIndexData($post->post_title, $data);
        PostTypeIndexer::buildIndexData($post->post_type, $this->getLabel(), $data);

        // Indexe le champ post_content seulement si le post type le supporte
        if (post_type_supports($post->post_type, 'editor')) {
            PostContentIndexer::buildIndexData($post->post_content, $data);
        }

        // Indexe le champ post_excerpt seulement si le post type le supporte
        if (post_type_supports($post->post_type, 'excerpt')) {
            PostExcerptIndexer::buildIndexData($post->post_excerpt, $data);
        }

        // Indexe le champ post_parent seulement si le type de post est hiérarchique
        if (is_post_type_hierarchical($post->post_type)) {
            PostParentIndexer::buildIndexData((int) $post->post_parent, $data);
        }

        // Indexe les taxonomies
        is_null($taxonomies) && $taxonomies = $this->getIndexedTaxonomies();
        foreach ($taxonomies as $taxonomy) {
            $terms = get_the_terms($post, $taxonomy->name);
            is_array($terms) && TaxonomyIndexer::buildIndexData($terms, $taxonomy, $data);
        }

        // Ok
        return $data;
    }

    /**
     * Retourne la liste des status à indexer.
     *
     * @return string[]
     */
    protected function getIndexedStatuses(): array
    {
        return ['publish', 'pending', 'private'];
    }

    /**
     * Retourne la liste des taxonomies à indexer et le nom de l'attribut de recherche à générer.
     *
     * L'implémentation par défaut retourne uniquement les taxonomies associé au type de post indexé qui
     * ont les propriétés "public" et "publicly_queryable" à true et le nom de l'attribut de recherche
     * généré est identique au nom de la taxonomie.
     *
     * @return WP_Taxonomy[] Un tableau d'objets WP_Taxonomy.
     */
    protected function getIndexedTaxonomies(): array
    {
        $result = [];
        $taxonomies = get_object_taxonomies($this->getType(), 'objects');
        foreach ($taxonomies as $taxonomy) { /** @var WP_Taxonomy $taxonomy */
            if ($taxonomy->public && $taxonomy->publicly_queryable && $taxonomy->show_ui) {
                $result[] = $taxonomy;
            }
        }

        return $result;
    }

    /**
     * Indexe un post.
     *
     * @param WP_Post       $post           Post à indexer.
     * @param IndexManager  $indexManager   Le gestionnaire d'index docalist-search.
     */
    final protected function index(WP_Post $post, IndexManager $indexManager): void
    {
        $indexManager->index($this->getType(), (int) $post->ID, $this->getIndexData($post));
    }

    /**
     * Supprime un post de l'index.
     *
     * @param WP_Post       $post           Post à désindexer.
     * @param IndexManager  $indexManager   Le gestionnaire d'index docalist-search.
     */
    final protected function remove(WP_Post $post, IndexManager $indexManager): void
    {
        $indexManager->delete($this->getType(), (int) $post->ID);
    }

    /**
     * {@inheritDoc}
     */
    final public function indexAll(IndexManager $indexManager): void
    {
        $wpdb = docalist('wordpress-database'); /* @var wpdb $wpdb */
        $offset = 0;
        $limit = 1000;

        // Prépare la requête utilisée pour charger les posts par lots de $limit
        $sql = sprintf(
            "SELECT * FROM %s WHERE post_type='%s' AND post_status IN ('%s') ORDER BY ID ASC LIMIT %%d OFFSET %%d",
            $wpdb->posts,
            $this->getType(),
            implode("','", $this->getIndexedStatuses())
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
                // On a un objet stdClass, il faut qu'on le convertisse en objet WP_Post
                // On pourrait utiliser get_post(post) mais dans ce cas, il se contente de faire new WP_Post
                $post = new WP_Post($post);
                $this->index($post, $indexManager);
            }

            // Passe au lot suivant
            $offset += count($posts);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function activateRealtime(IndexManager $indexManager): void
    {
        $statuses = array_flip($this->getIndexedStatuses());
        $type = $this->getType();

        add_action(
            'transition_post_status',
            function (string $new, string $old, WP_Post $post) use ($indexManager, $type, $statuses): void {
                // Si ce n'est pas un de nos posts, terminé
                if ($post->post_type !== $type) {
                    return;
                }

                // Si le nouveau statut est indexable, on indexe le post
                if (isset($statuses[$new])) {
                    $this->index($post, $indexManager);
                    return;
                }

                // Si le nouveau statut n'est pas indexable mais que l'ancien l'était, on désindexe le post
                if (isset($statuses[$old])) {
                    $this->remove($post, $indexManager);
                    return;
                }
            },
            10,
            3
        );

        add_action(
            'deleted_post',
            function (int $id) use ($indexManager, $type): void {
                // Avec deleted_post, on n'a que l'id : il faut qu'on charge le post pour vérifier le post_type.
                // Le post a déjà été supprimé de la base mais on peut quand même le charger car WordPress l'a
                // encore en cache (clean_post_cache() est appellée après l'action deleted_post).

                $post = get_post($id);
                if (!empty($post) && $post->post_type === $type) {
                    $this->remove($post, $indexManager);
                }
            }
        );
    }

    /**
     * Retourne un filtre permettant de limiter la recherche aux contenus auxquels l'utilisateur a accès.
     *
     * Le filtre retourné est de la forme "status:public OR createdby:user_login" (ou simplement "status:public" si
     * l'utilisateur en cours n'est pas connecté).
     *
     * @return array|null Retourne un filtre QueryDSL ou null si l'utilisateur dispose du droit "read_private_posts".
     */
    protected function getVisibilityFilter()
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
        foreach ($this->getIndexedStatuses() as $status) {
            $statusObject = get_post_status_object($status);
            $statusObject && $statusObject->public && $public[] = $status;
        }

        // L'utilisateur ne peut voir que les posts publics et ceux dont il est auteur
        $dsl = docalist('elasticsearch-query-dsl'); /* @var QueryDSL $dsl */
        $filter = $dsl->terms(PostStatusIndexer::CODE_FILTER, $public);
        is_user_logged_in() && $filter = $dsl->bool([
            $dsl->should($filter),
            $dsl->should($dsl->term(PostAuthorIndexer::LOGIN_FILTER, wp_get_current_user()->user_login))
        ]);

        // Ok
        return $filter;
    }

    /**
     * {@inheritDoc}
     */
    public function getSearchFilter(): array
    {
        $dsl = docalist('elasticsearch-query-dsl'); /* @var QueryDSL $dsl */

        $type = $dsl->term(CollectionIndexer::FILTER, $this->getCollection());
        $visibility = $this->getVisibilityFilter();

        // Construit un filtre de la forme "type:post AND (status:public OR createdby:user_login)"
        return $visibility ? $dsl->bool([$dsl->filter($type), $dsl->filter($visibility)]) : $type;
    }
}
