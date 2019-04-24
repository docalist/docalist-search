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
use Docalist\Search\Analysis\TokenFilter\English\EnglishPossessives;
use Docalist\Search\Analysis\TokenFilter\English\EnglishStop;
use Docalist\Search\Analysis\TokenFilter\English\EnglishStem;

/**
 * Analyseur "english_text" : permet une recherche "plein texte" sur un contenu en anglais (titre, résumé...)
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class EnglishText extends CustomAnalyzer
{
    /**
     * {@inheritDoc}
     */
    final public static function getName(): string
    {
        return 'english_text';
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
            EnglishPossessives::getName(),  // Supprime les "'s" à la fin des mots
            EnglishStop::getName(),         // Supprime les mots-vides
            'asciifolding',                 // Supprime les accents
            EnglishStem::getName(),         // Stemming standard
        ];

        // Remarques :
        // - Il y a des accents en anglais, donc asciifolding (http://en.wikipedia.org/wiki/Diacritic#English)
        // - Stop doit être exécuté après lowercase car les mots vides sont en minuscule dans la liste.
        //   Je l'ai mis avant asciifolding pour être cohérent avec ce que font les autres langues.
    }
}
