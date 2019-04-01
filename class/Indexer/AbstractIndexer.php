<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Search\Indexer;

use Docalist\Search\IndexManager;
use Docalist\Search\QueryDSL;
use Docalist\Search\Indexer;

/**
 * Classe de base abstraite pour les indexeurs.
 *
 * Cette classe implémente l'interface Indexer et fournit une API interne pour standardiser l'implémentation
 * des classes descendantes.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
abstract class AbstractIndexer implements Indexer
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

    public function getCollection()
    {
        return $this->getType();
    }

    public function buildIndexSettings(array $settings)
    {
        return $settings;
    }

    public function activateRealtime(IndexManager $indexManager)
    {
        return $this; // par défaut, ne fait rien (exemple NullIndexer)
    }

    public function indexAll(IndexManager $indexManager)
    {
        return $this; // par défaut, ne fait rien (exemple NullIndexer)
    }

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
    protected function index($content, IndexManager $indexManager)
    {
        $indexManager->index($this->getType(), $this->getID($content), $this->map($content));
    }

    /**
     * Supprime de l'index de recherche le contenu passé en paramètre.
     *
     * @param object|int $content Le contenu ou l'id du contenu à supprimer.
     * @param IndexManager $indexManager Le gestionnaire d'index docalist-search.
     */
    protected function remove($content, IndexManager $indexManager)
    {
        $indexManager->delete($this->getType(), is_scalar($content) ? $content : $this->getID($content));
    }

    public function getSearchFilter()
    {
        $dsl = docalist('elasticsearch-query-dsl'); /* @var QueryDSL $dsl */

        return $dsl->term('in', $this->getCollection());
    }
}
