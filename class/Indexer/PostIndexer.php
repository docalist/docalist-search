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

use Docalist\Search\Indexer\CustomPostTypeIndexer;

/**
 * Un indexeur pour les articles WordPress.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class PostIndexer extends CustomPostTypeIndexer
{
    /**
     * {@inheritDoc}
     */
    public function getType(): string
    {
        return 'post';
    }

    /**
     * {@inheritDoc}
     */
    public function getCollection(): string
    {
        return 'posts';
    }

    /**
     * {@inheritDoc}
     */
    public function getCategory(): string
    {
        return __('Contenus WordPress', 'docalist-search');
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(): string
    {
        return __('Blog du site', 'docalist-search');
    }
}
