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

namespace Docalist\Search\Analysis\Analyzer;

use Docalist\Search\Analysis\Analyzer\CustomAnalyzer;

/**
 * Analyseur "hierarchy" : permet d'indexer le path d'un tag dans une hiérarchie.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Hierarchy extends CustomAnalyzer
{
    /**
     * {@inheritDoc}
     */
    public static function getName(): string
    {
        return 'hierarchy';
    }

    /**
     * {@inheritDoc}
     */
    public function getTokenizer(): string
    {
        return 'path_hierarchy';
    }
}
