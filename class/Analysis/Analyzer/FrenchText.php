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
use Docalist\Search\Analysis\TokenFilter\French\FrenchElision;
use Docalist\Search\Analysis\TokenFilter\French\FrenchStop;
use Docalist\Search\Analysis\TokenFilter\French\FrenchStemMinimal;

/**
 * Analyseur "french_text" : permet une recherche "plein texte" sur un contenu en français (titre, résumé...)
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class FrenchText extends CustomAnalyzer
{
    /**
     * {@inheritDoc}
     */
    public static function getName(): string
    {
        return 'french_text';
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
            'lowercase',                    // Convertit le texte en minuscules
            FrenchElision::getName(),       // Supprime les élisions (c', d', l'...)
            FrenchStop::getName(),          // Supprime les mots-vides
            FrenchStemMinimal::getName(),   // Stemming minimal
            'asciifolding',                 // Supprime les accents (après le stemming, teste les accents)
        ];

        // Remarques :
        // - elision doit ête exécuté après lowercase (enlève que les minus)
        // - stop doit être exécuté après lowercase car les mots vides sont en
        //   minuscule dans la liste, mais avant asciifolding car ils peuvent
        //   avoir des accents.
    }
}
