<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2012-2017 Daniel Ménard
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
use Docalist\Search\MappingBuilder;
use Docalist\Search\QueryDSL;
use WP_Post;
use wpdb;
use WP_Term;
use stdClass;
use Docalist\Tokenizer;

/**
 * Classe de base pour les indexeurs qui gèrent des objets WP_Post (posts, pages, custom post types, etc.)
 */
class CustomPostTypeIndexer extends AbstractIndexer
{
    /**
     * Le type de posts gérés par cet indexeur (nom du custom post type).
     *
     * @var string
     */
    protected $type;

    /**
     * Le nom utilisé pour rechercher ces posts dans le moteur de recherche (champ "in:").
     *
     * @var string
     */
    protected $collection;

    /**
     * Un libellé indiquant la catégorie à laquelle appartient cet indexeur.
     *
     * @var string
     */
    protected $category;

    /**
     * Liste des status à indexer.
     *
     * @var string[]
     */
    protected $statuses = ['publish', 'pending', 'private'];

    /**
     * Liste des taxonomies indexées pour les posts gérés par cet indexeur.
     *
     * La propriété est initialisée lors du premier appel à getIndexedTaxonomies().
     *
     * @var string[] Un tableau de la forme taxonomie => champ elasticsearch généré (identiques par défaut).
     */
    protected $indexedTaxonomies;

    /**
     * Initialise l'indexeur.
     *
     * @var string $type        Le type de posts gérés par cet indexeur (nom du custom post type).
     * @var string $collection  Le nom utilisé pour rechercher ces posts dans le moteur de recherche (champ "in:")
     *                          Par défaut, identique à $type.
     * @var string $category    Un libellé indiquant la catégorie à laquelle appartient cet indexeur. Cette catégorie
     *                          est utilisée dans l'administration de docalist-search pour classer les différents
     *                          types d'indexeurs. Par défaut (ou null), c'est "Custom posts types".
     */
    public function __construct($type, $collection = null, $category = null)
    {
        $this->type = $type;
        $this->collection = $collection ?: $type;
        $this->category = $category;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getLabel()
    {
        return get_post_type_object($this->getType())->labels->name;
    }

    public function getCategory()
    {
        return $this->category ?: __('Custom posts types', 'docalist-search');
    }

    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * Retourne la liste des status à indexer.
     *
     * @return string
     */
    public function getStatuses()
    {
        return $this->statuses;
    }

    /**
     * Définit la liste des status à indexer.
     *
     * @param string[] $statuses
     *
     * return self
     */
    public function setStatuses(array $statuses)
    {
        $this->statuses = $statuses;

        return $this;
    }

    public function buildIndexSettings(array $settings)
    {
        $mapping = docalist('mapping-builder'); /** @var MappingBuilder $mapping */
        $mapping->reset()->setDefaultAnalyzer('fr-text'); // todo : rendre configurable

        $mapping->addField('in')->keyword();
        $mapping->addField('type')->keyword();
        $mapping->addField('type-label')->text()->filter();
        $mapping->addField('status')->keyword();
        $mapping->addField('slug')->text();
        $mapping->addField('createdby')->keyword();
        $mapping->addField('creation')->dateTime();
        $mapping->addField('lastupdate')->dateTime();
        $mapping->addField('posttitle')->text();
        $mapping->addField('posttitle-sort')->keyword();
        $mapping->addField('content')->text();
        $mapping->addField('excerpt')->text();
        if (is_post_type_hierarchical($this->getType())) {
            $mapping->addField('parent')->integer();
        }

        // Taxonomies associées à ce post_type
        foreach($this->getIndexedTaxonomies() as $taxonomy => $field) {
            $mapping->addField($field)->keyword();
            if (is_taxonomy_hierarchical($taxonomy)) {
                $mapping->addField($field . '-hierarchy')->text('hierarchy');
            }
        }

        $settings['mappings'][$this->getType()] = $mapping->getMapping();

        return $settings;
    }

    /**
     * Retourne les taxonomies indexées par cet indexeur et le nom du champ elasticsearch correspondant.
     *
     * La méthode retourne le nom des taxonomies qui sont associées à ce type de post et pour lesquelles
     * la méthode getTaxonomyField() retourne un nom de champ.
     *
     * Remarque : lors du premier appel, le résultat est mis en cache dans la propriété $indexedTaxonomies
     * et c'est cette propriété qui est retournée lors des appels ultérieurs.
     *
     * @return string[] Un tableau de la forme taxonomie => champ elasticsearch généré.
     */
    final protected function getIndexedTaxonomies()
    {
        if (is_null($this->indexedTaxonomies)) {
            $this->indexedTaxonomies = [];
            foreach(get_object_taxonomies($this->getType(), 'objects') as $name => $taxonomy) {
                $field = $this->getTaxonomyField($taxonomy);
                !empty($field) && $this->indexedTaxonomies[$name] = $field;
            }
        }

        return $this->indexedTaxonomies;
    }

    /**
     * Retourne le nom du champ elasticsearch à générer pour la taxonomie passée en paramètre.
     *
     * Par défaut, la méthode retourne un nom de champ pour les taxonomies qui ont les propriétés "visible"
     * et "publicly_queryable" à true.
     *
     * Par défaut, le nom du champ elasticsearch qui est généré est identique au nom de la  taxonomie
     * (exemple : "category"), sauf pour la taxonomie "post_tag" qui est renommée "tag".
     *
     * Les classes descendantes peuvent surcharger cette méthode pour changer les taxonomies qui sont ou non
     * indexées et modifier le nom du champ elasticsearch généré.
     *
     * @param stdClass $taxonomy Taxonomie à indexer.
     *
     * @return string|NULL Retourne un nom de champ ou null si la taxonomie indiquée ne doit pas être indexée.
     */
    protected function getTaxonomyField(/* WP_Taxonomy */ $taxonomy)
    {
        if ($taxonomy->public && $taxonomy->publicly_queryable) {
            return ($taxonomy->name === 'post_tag') ? 'tag' : $taxonomy->name;
        }

        return null;
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
        $wpdb = docalist('wordpress-database'); /** @var wpdb $wpdb */
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

    protected function getID($post) /** @var WP_Post $post */
    {
        return $post->ID;
    }

    protected function map($post) /** @var WP_Post $post */
    {
        $document = [];

        // Nom de la collection (in)
        $document['in'] = $this->getCollection();

        // Type
        $document['type'] = $this->getType();
        $document['type-label'] = $this->getLabel();

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

        // Titre du post
        if ($post->post_title) {
            $document['posttitle'] = $post->post_title;
            $document['posttitle-sort'] = implode(' ', Tokenizer::tokenize($post->post_title));
        }

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

        // Taxonomies associées à ce post
        foreach($this->getIndexedTaxonomies() as $taxonomy => $field) {
            if (is_array($terms = get_the_terms($post, $taxonomy))) {
                $hierarchy = is_taxonomy_hierarchical($taxonomy) ? ($field . '-hierarchy') : false;

                $document[$field] = [];
                $hierarchy && $document[$hierarchy] = [];
                foreach($terms as $term) { /** @var WP_Term $term */
                    $document[$field][] = $term->slug;

                    if ($hierarchy) {
                        $path = $term->slug;
                        foreach(get_ancestors($term->term_id, $taxonomy, 'taxonomy') as $ancestor) {
                            $term = get_term($ancestor, $taxonomy);
                            $path = $term->slug . '/' . $path;
                        }
                        $document[$hierarchy][] = $path;
                    }
                }
            }
        }

        return $document;
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
        foreach ($this->getStatuses() as $status) {
            $statusObject = get_post_status_object($status);
            $statusObject && $statusObject->public && $public[] = $status;
        }

        // L'utilisateur ne peut voir que les posts publics et ceux dont il est auteur
        $dsl = docalist('elasticsearch-query-dsl'); /** @var QueryDSL $dsl */
        $filter = $dsl->terms('status', $public);
        is_user_logged_in() && $filter = $dsl->bool([
            $dsl->should($filter),
            $dsl->should($dsl->term('createdby', wp_get_current_user()->user_login))
        ]);

        // Ok
        return $filter;
    }

    public function getSearchFilter()
    {
        $dsl = docalist('elasticsearch-query-dsl'); /** @var QueryDSL $dsl */

        $type = parent::getSearchFilter();
        $visibility = $this->getVisibilityFilter();

        // Construit un filtre de la forme "type:post AND (status:public OR createdby:user_login)"
        return $visibility ? $dsl->bool([$dsl->filter($type), $dsl->filter($visibility)]) : $type;
    }
}
