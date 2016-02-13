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
namespace Docalist\Search;

use Psr\Log\LoggerInterface;

/**
 * Classe de base abstraite pour les indexeurs.
 *
 * Le rôle d'un indexeur consiste à transformer un objet stocké dans la base de données (article, page, notice,
 * utilisateur, commentaire, produit...) en document destiné à être indexé par ElasticSearch.
 *
 * Chaque indexeur gère un seul type de contenu et dispose de méthode permettant de convertir un objet en document
 * ElasticSearch, d'indexer / mettre à jour / supprimer un objet, de réindexer la totalité des objets de ce type.
 *
 * Cette classe de base contient également des méthodes qui permettent de mapper de façon homogène entre différents
 * types certains des champs standard de WordPress (post_status, post_title, etc.)
 */
abstract class TypeIndexer
{
    /**
     * Le type de contenu géré par cet indexeur (post_type, comment, user...).
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

    /*
     * API publique des indexeurs (méthodes qui sont appellées depuis l'extérieur)
     */

    /**
     * Construit un nouvel indexeur.
     *
     * @param string $type Le type de contenu géré par cet indexeur (nom du post_type, comment, user, etc...)
     */
    public function __construct($type)
    {
        $this->type = $type;
        $this->log = docalist('logs')->get('indexer');
    }

    /**
     * Retourne le type de contenu géré par cet indexeur.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Retourne le libellé à utiliser pour désigner les contenus gérés par cet indexeur.
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->type;
    }

    /**
     * Retourne la catégorie dans laquelle figure les contenus gérés par cet indexeur (WordPress, Bases docalist...).
     *
     * @return string
     */
    public function getCategory()
    {
        return __('Autres contenus', 'docalist-search');
    }

    /**
     * Construit les settings de l'index ElasticSearch.
     *
     * Cette méthode permet à l'indexeur d'ajouter dans les settings de l'index les analyseurs et les mappings
     * dont il a besoin.
     *
     * La création des settings de l'index est un processus distribué entre les différents indexeurs et chaque
     * indexeur doit veiller à ne pas écraser les settings définis préalablement par un autre indexeur.
     *
     * @param array $settings Les settings à mettre à jour.
     *
     * @return array Les settings modifiés.
     */
    public function buildIndexSettings(array $settings)
    {
        return $settings;
    }

    /**
     * Installe les hooks nécessaires pour permettre l'indexation en temps réel des contenus créés, modifiés
     * ou supprimés.
     */
    abstract public function activeRealtime();

    /**
     * Indexe tous les documents de ce type.
     *
     * @param Indexer $indexer
     * @param string $type
     */
    abstract public function indexAll(Indexer $indexer);

    /*
     * API interne des indexeurs (méthodes destinées aux classes descendantes)
     */

    /**
     * Retourne un identifiant unique pour le contenu passé en paramètre.
     *
     * Cette méthode est surchargée dans les classes descendantes : elle retourne le Post ID pour un post,
     * le User ID pour un utilisateur, etc.
     *
     * @param object $content
     *
     * @return int L'identifiant du contenu.
     */
    abstract protected function getID($content);

    /**
     * Transforme le contenu passé en paramètre en document destiné à être indexé par ElasticSearch.
     *
     * @param object $content Le contenu à convertir.
     *
     * @return array Le document ElasticSearch obtenu.
     */
    abstract protected function map($content);

    /**
     * Indexe ou réindexe le contenu passé en paramètre.
     *
     * @param object $content
     * @param Indexer $indexer L'indexeur a utiliser
     */
    final protected function index($content, Indexer $indexer)
    {
        $indexer->index($this->getType(), $this->getID($content), $this->map($content));
    }

    /**
     * Supprime de l'index de recherche le contenu passé en paramètre.
     *
     * @param object|int $content Le contenu ou l'id du contenu à supprimer.
     * @param Indexer $indexer L'indexeur a utiliser
     */
    final protected function remove($content, Indexer $indexer)
    {
        $id = is_scalar($content) ? $content : $this->getID($content);
        $indexer->delete($this->getType(), $id);
    }
}
