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

/**
 * Un indexeur pour les pages WordPress.
 */
class PageIndexer extends PostIndexer
{
    public function getType()
    {
        return 'page';
    }

    public function getCollection()
    {
        return 'pages';
    }
}
