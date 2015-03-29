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

/**
 * Analyseurs et filtres spécifiques à l'anglais.
 *
 * Inspiré de :
 * http://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-lang-analyzer.html#english-analyzer
 *
 * @return array
 */
return [
    /* --------------------------------------------------------------
     * filter : traitement des tokens
     * -------------------------------------------------------------- */
    'filter' => [

        /*
         * Filtre en-possessives : supprime les "'s" à la fin des mots.
         */
        'en-possessives' => [
            'type' => 'stemmer',
            'language' => 'possessive_english'
        ],

        /*
         * Filtre en-stop : supprime les mots vides anglais.
         *
         * @see https://github.com/apache/lucene-solr/blob/trunk/lucene/analysis/common/src/java/org/apache/lucene/analysis/core/StopAnalyzer.java#L42
         */
        'en-stop' => [
            'type' => 'stop',
            'stopwords' => ['_english_']
        ],

        /*
         * Filtre en-stem : stemmer anglais standard (snowball, Martin Porter)
         *
         * @see http://snowball.tartarus.org/texts/introduction.html
         * @see http://snowball.tartarus.org/algorithms/english/stemmer.html
         * @see http://www.lirmm.fr/~mroche/Recherche/Articles/Porter/porter.pdf
         */
        'en-stem' => [
            'type' => 'stemmer',
            'name' => 'english'
        ],

        /*
         * Filtre en-stem-light : stemmer anglais light (Robert Krovetz)
         *
         * Moins aggressif que stem.
         *
         * @see http://ciir.cs.umass.edu/pubfiles/ir-35.pdf
         */
        'en-stem-light' => [
            'type' => 'stemmer',
            'name' => 'light_english'
        ],

        /*
         * Filtre en-stem-minimal : stemmer anglais minimal (Donna Harman)
         *
         * Encore moins aggressif que stem-light.
         *
         * @see http://www.researchgate.net/publication/220433848_How_effective_is_suffixing
         */
        'en-stem-minimal' => [
            'type' => 'stemmer',
            'name' => 'minimal_english'
        ],
    ],

    /* --------------------------------------------------------------
     * analyzer : analyseurs pré-définis
     * -------------------------------------------------------------- */
    'analyzer' => [
        /*
         * en-text : permet une recherche "plein texte" sur le contenu
         * d'un champ en anglais (titre, résumé...) en appliquant le
         * stemmer "en-stem".
         */
        'en-text' => [
            'type' => 'custom',

            'char_filter' => [
                'html_strip'        // Supprime les tags html
            ],

            'filter' => [
                'lowercase',        // Convertit le texte en minuscules
                'en-possessives',   // Supprime les "'s" à la fin des mots
                'en-stop',          // Supprime les mots-vides
                'asciifolding',     // Supprime les accents
                'en-stem'           // Stemming standard
            ],

            'tokenizer' => 'standard',
        ],
        // Remarques :
        // - il y a des accents en anglais, donc asciifolding
        //   (http://en.wikipedia.org/wiki/Diacritic#English)
        // - stop doit être exécuté après lowercase car les mots vides sont en
        //   minuscule dans la liste. Je l'ai mis avant asciifolding pour être
        //   cohérent avec ce que font les autres langues.
    ],
];