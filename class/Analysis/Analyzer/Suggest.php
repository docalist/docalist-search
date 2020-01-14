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

namespace Docalist\Search\Analysis\Analyzer;

use Docalist\Search\Analysis\Analyzer\CustomAnalyzer;

/**
 * Analyseur "suggest" : analyseur pour les lookups (autocomplete).
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class Suggest extends CustomAnalyzer
{
    /**
     * {@inheritDoc}
     */
    final public static function getName(): string
    {
        return 'suggest';
    }

    /**
     * {@inheritDoc}
     */
    final public function getTokenizer(): string
    {
        return 'keyword';
    }

    /**
     * {@inheritDoc}
     */
    final public function getTokenFilters(): array
    {
        return [
            'lowercase',        // Convertit le texte en minuscules
            'asciifolding',     // Supprime les accents
        ];
    }
}
