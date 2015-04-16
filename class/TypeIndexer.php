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

use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Classe de base abstraite pour les indexeurs.
 *
 * Le rôle d'un indexeur consiste à transformer un objet stocké dans la base de
 * données (article, page, notice, utilisateur, commentaire, produit...) en
 * document destiné à être indexé par ElasticSearch.
 *
 * Chaque indexeur gère un seul type de contenu et dispose de méthode permettant
 * de convertir un objet en document ElasticSearch, d'indexer / mettre à jour /
 * supprimer un objet, de réindexer la totalité des objets de ce type.
 *
 * Cette classe de base contient également des méthodes qui permettent de
 * mapper de façon homogène entre différents types certains des champs standard
 * de WordPress (post_status, post_title, etc.)
 */
abstract class TypeIndexer {
    /**
     * Liste des champs WordPress standard qu'on sait indexer.
     *
     * @var string[]
     */
    protected static $stdFields = [
        'ID', 'post_type', 'post_status', 'post_name', 'post_parent',
        'post_author', 'post_date', 'post_modified', 'post_title',
        'post_content',  'post_excerpt'
    ];

    /**
     * Le type de contenu géré par cet indexeur (post_type, comment, user...)
     *
     * @var string
     */
    protected $type;

    /**
     * Le logger à utiliser.
     *
     * @var LoggerInterface
     */
    protected $log;

    /**
     * Construit un nouvel indexeur.
     *
     * @param string $type Le type de contenu géré par cet indexeur
     * (nom du post_type, comment, user, etc...)
     */
    public function __construct($type) {
        $this->type = $type;
        $this->log = docalist('logs')->get('indexer');
    }

    /**
     * Installe les hooks nécessaires pour permettre l'indexation en temps réel
     * des contenus créés, modifiés ou supprimés.
     */
    abstract public function realtime();

    /**
     * Retourne le type de contenu géré par cet indexeur.
     *
     * @return string
     */
    public function type() {
        return $this->type;
    }

    /**
     * Retourne un identifiant unique pour le contenu passé en paramètre.
     *
     * Cette méthode est surchargée dans les classes descendantes : elle
     * retourne le Post ID pour un post, le User ID pour un utilisateur, etc.
     *
     * @param object $content
     *
     * @return int L'identifiant du contenu.
     */
    abstract public function contentId($content);

    /**
     * Retourne le mapping ElasticSearch pour ce type.
     *
     * @return array
     */
    public function mapping() {
        return [];
    }

    /**
     * Transforme le contenu passé en paramètre en document destiné
     * à être indexé par ElasticSearch.
     *
     * @param object $content Le contenu à convertir.
     *
     * @return array Le document ElasticSearch obtenu.
     */
    abstract public function map($content);

    /**
     * Indexe ou réindexe le contenu passé en paramètre.
     *
     * @param object $content
     * @param Indexer $indexer L'indexeur a utiliser
     */
    public function index($content, Indexer $indexer) {
        $indexer->index(
            $this->type(),
            $this->contentId($content),
            $this->map($content)
        );
    }

    /**
     * Supprime de l'index de recherche le contenu passé en paramètre.
     *
     * @param object|int $content Le contenu ou l'id du contenu à supprimer.
     * @param Indexer $indexer L'indexeur a utiliser
     */
    public function remove($content, Indexer $indexer) {
        $id = is_scalar($content) ? $content : $this->contentId($content);
        $indexer->delete($this->type(), $id);
    }

    /**
     * Réindexe tous les documents de ce type.
     *
     * @param Indexer $indexer
     * @param string $type
     */
    abstract public function indexAll(Indexer $indexer);

    /**
     * Génère le mapping standard à utiliser pour un champ WordPress.
     *
     * @param string $field Le nom d'un champ WP_Post.
     * @param MappingBuilder $mapping Le mapping à modifier.
     *
     * @throws InvalidArgumentException Si le champ indiqué n'est pas géré.
     */
    public static function standardMapping($field, MappingBuilder $mapping) {
        switch ($field) {
            case 'ID':
                return; // non indexé, on a déjà _id géré par ES

            case 'post_type':
                return; // non indexé, on a déjà _type géré par ES

            case 'post_status':
                return $mapping->field('status')->text()->filter();

            case 'post_name':
                return $mapping->field('slug')->text();

            case 'post_parent':
                return $mapping->field('parent')->long();

            case 'post_author':
                return $mapping->field('createdby')->text()->filter();

            case 'post_date':
                return $mapping->field('creation')->dateTime();

            case 'post_modified':
                return $mapping->field('lastupdate')->dateTime();

            case 'post_title':
                return $mapping->field('title')->text();

            case 'post_content':
                return $mapping->field('content')->text();

            case 'post_excerpt':
                return $mapping->field('excerpt')->text();

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
    public static function standardMap($field, $value, array & $document) {
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
                $document['parent'] = $value;

                return;

            case 'post_author':
                if (false !== $user = get_user_by( 'id', $value)) { /* @var $user WP_User */
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