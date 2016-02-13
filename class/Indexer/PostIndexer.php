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
use Docalist\Search\Indexer\AbstractIndexer;
use wpdb;
use WP_Post;
use Docalist\MappingBuilder;
use InvalidArgumentException;

/**
 * Un indexeur pour les articles de WordPress.
 */
class PostIndexer extends AbstractIndexer
{
    /**
     * Liste des champs WordPress standard qu'on sait indexer.
     *
     * @var string[]
     */
    protected static $stdFields = [
        'ID', 'post_type', 'post_status', 'post_name', 'post_parent', 'post_author', 'post_date', 'post_modified',
        'post_title', 'post_content',  'post_excerpt',
    ];

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

    public function buildIndexSettings(array $settings)
    {
        $mapping = docalist('mapping-builder'); /* @var MappingBuilder $mapping */
        $mapping->reset()->setDefaultAnalyzer('fr-text'); // todo : rendre configurable

        foreach (self::$stdFields as $field) {
            static::standardMapping($field, $mapping);
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
            function ($newStatus, $oldStatus, WP_Post $post) use ($indexManager, $type, $statuses)
            {
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
                    return $indexManager->delete($this->getType(), $this->getID($post));
                }
            },
            10, 3
        );

        add_action('deleted_post',
            function ($id) use ($indexManager)
            {
                $post = get_post($id);
                if ($post->post_type === $this->getType()) {
                    $indexManager->delete($this->getType(), $this->getID($post));
                }
            }
        );
    }

    public function indexAll(IndexManager $indexer)
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
                $this->index($post, $indexer);
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
        foreach (self::$stdFields as $field) {
            $value = $post->$field;
            $value && static::standardMap($field, $value, $document);
        }

        return $document;
    }

    /**
     * Génère le mapping standard à utiliser pour un champ WordPress.
     *
     * @param string $field Le nom d'un champ WP_Post.
     * @param MappingBuilder $mapping Le mapping à modifier.
     *
     * @throws InvalidArgumentException Si le champ indiqué n'est pas géré.
     */
    public static function standardMapping($field, MappingBuilder $mapping)
    {
        switch ($field) {
            case 'ID':              // non indexé, on a déjà _id géré par ES
            case 'post_type':       // non indexé, on a déjà _type géré par ES
                return;
            case 'post_status':     return $mapping->addField('status')->text()->filter();
            case 'post_name':       return $mapping->addField('slug')->text();
            case 'post_parent':     return $mapping->addField('parent')->integer();
            case 'post_author':     return $mapping->addField('createdby')->text()->filter();
            case 'post_date':       return $mapping->addField('creation')->dateTime();
            case 'post_modified':   return $mapping->addField('lastupdate')->dateTime();
            case 'post_title':      return $mapping->addField('title')->text();
            case 'post_content':    return $mapping->addField('content')->text();
            case 'post_excerpt':    return $mapping->addField('excerpt')->text();
            default:
                throw new InvalidArgumentException("Field '$field' not supported");
        }
    }

    /**
     * Mappe un champ WordPress standard.
     *
     * @param string $field Le nom du champ
     * @param la valeur du champ $value
     * @param array $document Le document à génerer.
     *
     * @throws InvalidArgumentException Si le champ indiqué n'est pas géré.
     */
    public static function standardMap($field, $value, array & $document)
    {
        switch ($field) {
            case 'ID':
                return; // non indexé, on a déjà _id géré par ES

            case 'post_type':
                return; // non indexé, on a déjà _type géré par ES

            case 'post_status':
                if (! is_null($status = get_post_status_object($value))) {
                    $value = $status->label;
                }
                $document['status'] = $value;

                return;

            case 'post_name':
                $document['slug'] = $value;

                return;

            case 'post_parent':
                $document['parent'] = (int) $value;

                return;

            case 'post_author':
                if (false !== $user = get_user_by('id', $value)) { /* @var $user WP_User */
                    $value = $user->user_login;
                }
                $document['createdby'] = $value;

                return;

            case 'post_date':
                $document['creation'] = $value;

                return;

            case 'post_modified':
                $document['lastupdate'] = $value;

                return;

            case 'post_title':
                $document['title'] = $value;

                return;

            case 'post_content':
                $document['content'] = $value;

                return;

            case 'post_excerpt':
                $document['excerpt'] = $value;

                return;

            default:
                throw new InvalidArgumentException("Field '$field' not supported");
        }
    }
}
