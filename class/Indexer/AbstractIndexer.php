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

/**
 * Classe de base abstraite pour les indexeurs.
 *
 * Cette classe implémente l'interface IndexerInterface et fournit une API interne pour standardiser l'implémentation
 * des classes descendantes.
 */
abstract class AbstractIndexer implements IndexerInterface
{
    abstract public function getType();

    public function getLabel()
    {
        return $this->getType();
    }

    public function getCategory()
    {
        return __('Autres contenus', 'docalist-search');
    }

    public function buildIndexSettings(array $settings)
    {
        return $settings;
    }

    abstract public function activateRealtime(IndexManager $indexManager);

    abstract public function indexAll(IndexManager $indexManager);

    /*
     * API interne des indexeurs (méthodes destinées à être surchargées par les classes descendantes)
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
     * @return array Le document ElasticSearch à indexer.
     */
    abstract protected function map($content);

    /**
     * Indexe ou réindexe le contenu passé en paramètre.
     *
     * @param object $content
     * @param IndexManager $indexManager Le gestionnaire d'index docalist-search.
     */
    final protected function index($content, IndexManager $indexManager)
    {
        $indexManager->index($this->getType(), $this->getID($content), $this->map($content));
    }

    /**
     * Supprime de l'index de recherche le contenu passé en paramètre.
     *
     * @param object|scalar $content Le contenu ou l'id du contenu à supprimer.
     * @param IndexManager $indexManager Le gestionnaire d'index docalist-search.
     */
    final protected function remove($content, IndexManager $indexManager)
    {
        $indexManager->delete($this->getType(), is_scalar($content) ? $content : $this->getID($content));
    }
}
