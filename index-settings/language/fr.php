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
 * Analyseurs et filtres spécifiques au français.
 *
 * Inspiré de :
 * http://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-lang-analyzer.html#french-analyzer
 *
 * @return array
 */
return [
    /* --------------------------------------------------------------
     * filter : traitement des tokens
     * -------------------------------------------------------------- */
    'filter' => [

        /*
         * Filtre fr-elision : supprime les élisions en français.
         *
         * @see https://github.com/apache/lucene-solr/blob/trunk/lucene/analysis/common/src/java/org/apache/lucene/analysis/fr/FrenchAnalyzer.java#L62
         */
        'fr-elision' => [
            'type' => 'elision',
            'articles' => [
                'l', 'm', 't', 'qu', 'n', 's','j', 'd', 'c', 'jusqu', 'quoiqu','lorsqu', 'puisqu'
            ]
        ],

        /*
         * Filtre fr-stop : supprime les mots vides français.
         *
         * cf. https://github.com/apache/lucene-solr/blob/trunk/lucene/analysis/common/src/resources/org/apache/lucene/analysis/snowball/french_stop.txt
         */
        'fr-stop' => [
            'type' => 'stop',
            'stopwords' => ['_french_']
        ],

        /*
         * Filtre fr-stem : stemmer français standard (snowball, Martin Porter)
         *
         * @see http://snowball.tartarus.org/texts/introduction.html
         * @see http://snowball.tartarus.org/algorithms/french/stemmer.html
         * @see http://www.lirmm.fr/~mroche/Recherche/Articles/Porter/porter.pdf
         */
        'fr-stem' => [
            'type' => 'stemmer',
            'name' => 'french'
        ],

        /*
         * Filtre fr-stem-light : stemmer français light (Jacques Savoy)
         *
         * Moins aggressif que stem.
         *
         * @see https://doc.rero.ch/record/13225/files/Savoy_Jacques_-_Light_Stemming_Approaches_fo_the_French_20091216.pdf
         * @see http://members.unine.ch/jacques.savoy/clef/frenchStemmerPlus.txt
         * @see org.apache.lucene.analysis.fr.FrenchLightStemmer.java
         */
        'fr-stem-light' => [
            'type' => 'stemmer',
            'name' => 'light_french'
        ],

        /*
         * Filtre fr-stem-minimal : stemmer français minimal (Jacques Savoy)
         *
         * Encore moins aggressif que stem-light.
         *
         * @see http://members.unine.ch/jacques.savoy/papers/frjasis.pdf
         * @see http://members.unine.ch/jacques.savoy/clef/frenchStemmer.txt
         */
        'fr-stem-minimal' => [
            'type' => 'stemmer',
            'name' => 'minimal_french'
        ],
    ],

    /* --------------------------------------------------------------
     * analyzer : analyseurs pré-définis
     * -------------------------------------------------------------- */
    'analyzer' => [
        /*
         * fr-text : permet une recherche "plein texte" sur le contenu
         * d'un champ en français (titre, résumé...) en appliquant le
         * stemmer "fr-stem-light".
         */
        'fr-text' => [
            'type' => 'custom',

            'char_filter' => [
                'html_strip'    // Supprime les tags html
            ],

            'filter' => [
                'lowercase',    // Convertit le texte en minuscules
                'fr-elision',   // Supprime les élisions (c', d', l'...)
                'fr-stop',      // Supprime les mots-vides
                'asciifolding', // Supprime les accents
                'fr-stem-light' // Stemming léger
            ],

            'tokenizer' => 'standard',
        ],
        // Remarques :
        // - elision doit ête exécuté après lowercase (enlève que les minus)
        // - stop doit être exécuté après lowercase car les mots vides sont en
        //   minuscule dans la liste, mais avant asciifolding car ils peuvent
        //   avoir des accents.
    ],
];