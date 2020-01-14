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
 * Analyseur "text" : permet une recherche "plein texte" sur un contenu (nom de personne, nom d'organisme)
 * sans tenir compte de la langue (pas de stemming).
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class Text extends CustomAnalyzer
{
    /**
     * {@inheritDoc}
     */
    final public static function getName(): string
    {
        return 'text';
    }

    /**
     * {@inheritDoc}
     */
    final public function getCharFilters(): array
    {
        return [
            'html_strip',       // Supprime les tags html
        ];
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
