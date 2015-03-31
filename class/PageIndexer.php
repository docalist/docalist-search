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

/**
 * Un indexeur pour les pages WordPress.
 */
class PageIndexer extends PostIndexer {
    /**
     * Construit l'indexeur.
     */
    public function __construct() {
        parent::__construct('page');
    }
}