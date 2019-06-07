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

use Docalist\Search\Indexer\PostIndexer;

/**
 * Un indexeur pour les pages WordPress.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class PageIndexer extends PostIndexer
{
    /**
     * {@inheritDoc}
     */
    public function getType(): string
    {
        return 'page';
    }

    /**
     * {@inheritDoc}
     */
    public function getCollection(): string
    {
        return 'pages';
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(): string
    {
        return __('Pages du site', 'docalist-search');
    }
}
