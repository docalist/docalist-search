<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2020 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Search\Indexer;

use Docalist\Search\Indexer;
use Docalist\Search\IndexManager;
use Docalist\Search\Mapping;

/**
 * Un indexeur qui ne fait rien.
 *
 * Cette classe sert à remplacer un indexeur manquant ou invalide (cf. la méthode IndexManager::getIndexer()).
 *
 * Quand on désactive un plugin, on peut se retrouver avec un contenu indexé pour lequel l'indexeur n'est plus
 * disponible (par exemple, on a indexé une base documentaire puis on désactive docalist-biblio). Cette classe sert
 * à gérer ce cas : au lieu de générer une erreur fatale, l'indexeur manquant est remplacé par un NullIndexer et une
 * admin notice est générée.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class MissingIndexer implements Indexer
{
    /**
     * Le type pour lequel l'indexeur n'est pas disponible.
     *
     * @var string
     */
    private $type;

    /**
     * Initialise l'indexeur manquant.
     *
     * @param string $type Le type pour lequel l'indexeur n'est pas disponible.
     */
    public function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * {@inheritDoc}
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * {@inheritDoc}
     */
    public function getCollection(): string
    {
        return $this->type;
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(): string
    {
        return sprintf(__('Type %s (indexeur manquant)', 'docalist-search'), $this->type);
    }

    /**
     * {@inheritDoc}
     */
    public function getCategory(): string
    {
        return __('Indexeurs manquants', 'docalist-search');
    }

    /**
     * {@inheritDoc}
     */
    public function getMapping(): Mapping
    {
        return new Mapping($this->type);
    }

    /**
     * {@inheritDoc}
     */
    public function indexAll(IndexManager $indexManager): void
    {
        // no-op
    }

    /**
     * {@inheritDoc}
     */
    public function activateRealtime(IndexManager $indexManager): void
    {
        // no-op
    }

    /**
     * {@inheritDoc}
     */
    public function getSearchFilter(): array
    {
        return [];
    }
}
