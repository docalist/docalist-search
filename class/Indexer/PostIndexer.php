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

/**
 * Un indexeur pour les articles de WordPress.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class PostIndexer extends CustomPostTypeIndexer
{
    public function __construct()
    {
        parent::__construct('post', 'posts', __('Contenus WordPress', 'docalist-search'));
    }

    public function getLabel()
    {
        return __('Blog du site', 'docalist-search');
    }
}
