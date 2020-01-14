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
use Docalist\Search\Analysis\TokenFilter\Spanish\SpanishStop;
use Docalist\Search\Analysis\TokenFilter\Spanish\SpanishStemLight;

/**
 * Analyseur "spanish_text" : permet une recherche "plein texte" sur un contenu en espagnol (titre, résumé...)
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class SpanishText extends CustomAnalyzer
{
    /**
     * {@inheritDoc}
     */
    final public static function getName(): string
    {
        return 'spanish_text';
    }

    /**
     * {@inheritDoc}
     */
    final public function getCharFilters(): array
    {
        return [
            'html_strip',           // Supprime les tags html
        ];
    }

    /**
     * {@inheritDoc}
     */
    final public function getTokenFilters(): array
    {
        return [
            'lowercase',                    // Convertit le texte en minuscules
            SpanishStop::getName(),         // Supprime les mots-vides
            'asciifolding',                 // Supprime les accents
            SpanishStemLight::getName(),    // Stemming léger
        ];

        // Remarques :
        // - il y a des accents en espagnol, donc asciifolding
        // - stop doit être exécuté après lowercase car les mots vides sont en
        //   minuscule dans la liste, mais avant asciifolding car ils peuvent
        //   avoir des accents.
    }
}
