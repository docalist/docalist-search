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
use Docalist\Search\Analysis\TokenFilter\Italian\ItalianElision;
use Docalist\Search\Analysis\TokenFilter\Italian\ItalianStop;
use Docalist\Search\Analysis\TokenFilter\Italian\ItalianStemLight;

/**
 * Analyseur "italian_text" : permet une recherche "plein texte" sur un contenu en français (titre, résumé...)
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class ItalianText extends CustomAnalyzer
{
    /**
     * {@inheritDoc}
     */
    final public static function getName(): string
    {
        return 'italian_text';
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
            ItalianElision::getName(),      // Supprime les élisions (c', d', l'...)
            ItalianStop::getName(),         // Supprime les mots-vides
            'asciifolding',                 // Supprime les accents
            ItalianStemLight::getName(),    // Stemming léger
        ];
        // Remarques :
        // - il y a des accents en italien, donc asciifolding
        // - elision doit ête exécuté après lowercase (enlève que les minus)
        // - stop doit être exécuté après lowercase car les mots vides sont en
        //   minuscule dans la liste, mais avant asciifolding car ils peuvent
        //   avoir des accents.
    }
}
