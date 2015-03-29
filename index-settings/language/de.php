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
 * Analyseurs et filtres spécifiques à l'allemand.
 *
 * Inspiré de :
 * http://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-lang-analyzer.html#german-analyzer
 *
 * @return array
 */
return [
    /* --------------------------------------------------------------
     * filter : traitement des tokens
     * -------------------------------------------------------------- */
    'filter' => [

        /*
         * Filtre de-stop : supprime les mots vides allemands.
         *
         * @see https://github.com/apache/lucene-solr/blob/trunk/lucene/analysis/common/src/resources/org/apache/lucene/analysis/snowball/german_stop.txt
         */
        'de-stop' => [
            'type' => 'stop',
            'stopwords' => ['_german_']
        ],

        /*
         * Filtre de-stem : stemmer allemand standard (snowball, Martin Porter)
         *
         * @see http://snowball.tartarus.org/texts/introduction.html
         * @see http://snowball.tartarus.org/algorithms/german/stemmer.html
         * @see http://www.lirmm.fr/~mroche/Recherche/Articles/Porter/porter.pdf
         */
        'de-stem' => [
            'type' => 'stemmer',
            'name' => 'german'
        ],

        /*
         * Filtre de-stem2 : variation du stemmer allemand standard qui tient
         * compte des lettres ä, ö and ü (snowball, Martin Porter)
         *
         * @see http://snowball.tartarus.org/texts/introduction.html
         * @see http://snowball.tartarus.org/algorithms/german2/stemmer.html
         * @see http://www.lirmm.fr/~mroche/Recherche/Articles/Porter/porter.pdf
         */
        'de-stem2' => [
            'type' => 'stemmer',
            'name' => 'german'
        ],

        /*
         * Filtre de-stem-light : stemmer allemand light (Jacques Savoy)
         *
         * Moins aggressif que stem.
         *
         * @see http://dl.acm.org/citation.cfm?id=1141523
         */
        'de-stem-light' => [
            'type' => 'stemmer',
            'name' => 'light_german'
        ],

        /*
         * Filtre de-stem-minimal : stemmer allemand minimal (Jacques Savoy)
         *
         * Encore moins aggressif que stem-light.
         *
         * @see http://members.unine.ch/jacques.savoy/clef/morpho.pdf
         */
        'de-stem-minimal' => [
            'type' => 'stemmer',
            'name' => 'minimal_german'
        ],
    ],

    /* --------------------------------------------------------------
     * analyzer : analyseurs pré-définis
     * -------------------------------------------------------------- */
    'analyzer' => [
        /*
         * de-text : permet une recherche "plein texte" sur le contenu
         * d'un champ en allemand (titre, résumé...) en appliquant le
         * stemmer "de-stem-light".
         */
        'de-text' => [
            'type' => 'custom',

            'char_filter' => [
                'html_strip'            // Supprime les tags html
            ],

            'filter' => [
                'lowercase',            // Convertit le texte en minuscules
                'de-stop',              // Supprime les mots-vides
             // 'german_normalization', // Convertit ß, ä, ö, ü, etc.
                'asciifolding',         // Supprime les accents
                'de-stem-light'         // Stemming léger
            ],

            'tokenizer' => 'standard',
        ],
        // Remarques :
        // - il y a des accents en allemand, donc asciifolding ou german_normalization
        //   (http://fr.wikipedia.org/wiki/Diacritique#Diacritiques_non_français_de_l'alphabet_latin)
        // - stop doit être exécuté après lowercase car les mots vides sont en
        //   minuscule dans la liste, mais avant ascii asciifolding car ils
        //   peuvent contenir des caractères comme ß, ä, etc.
    ],
];