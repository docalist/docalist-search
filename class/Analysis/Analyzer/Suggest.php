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
 * Analyseur "suggest" : analyseur pour les lookups (autocomplete).
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Suggest extends CustomAnalyzer
{
    /**
     * {@inheritDoc}
     */
    public static function getName(): string
    {
        return 'suggest';
    }

    /**
     * {@inheritDoc}
     */
    public function getTokenizer(): string
    {
        return 'keyword';
    }

    /**
     * {@inheritDoc}
     */
    public function getTokenFilters(): array
    {
        return [
            'lowercase',        // Convertit le texte en minuscules
            'asciifolding',     // Supprime les accents
        ];
    }
}
