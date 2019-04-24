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
use Docalist\Search\Analysis\TokenFilter\German\GermanStop;
use Docalist\Search\Analysis\TokenFilter\German\GermanStemLight;

/**
 * Analyseur "german_text" : permet une recherche "plein texte" sur un contenu en allemand (titre, résumé...)
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class GermanText extends CustomAnalyzer
{
    /**
     * {@inheritDoc}
     */
    public static function getName(): string
    {
        return 'german_text';
    }

    /**
     * {@inheritDoc}
     */
    public function getCharFilters(): array
    {
        return [
            'html_strip',           // Supprime les tags html
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getTokenFilters(): array
    {
        return [
            'lowercase',                // Convertit le texte en minuscules
            GermanStop::getName(),      // Supprime les mots-vides
            'asciifolding',             // Supprime les accents
            GermanStemLight::getName(), // Stemming léger
        ];

        // Remarques :
        // - il y a des accents en allemand, donc asciifolding ou german_normalization
        //   (http://fr.wikipedia.org/wiki/Diacritique#Diacritiques_non_français_de_l'alphabet_latin)
        // - stop doit être exécuté après lowercase car les mots vides sont en
        //   minuscule dans la liste, mais avant ascii asciifolding car ils
        //   peuvent contenir des caractères comme ß, ä, etc.
    }
}
