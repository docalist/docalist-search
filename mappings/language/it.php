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
 * Analyseurs et filtres spécifiques à l'italien.
 *
 * Inspiré de :
 * http://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-lang-analyzer.html#italian-analyzer
 *
 * @return array
 */
return [
    /* --------------------------------------------------------------
     * filter : traitement des tokens
     * -------------------------------------------------------------- */
    'filter' => [

        /*
         * Filtre it-elision : supprime les élisions en italien.
         *
         * @see https://github.com/apache/lucene-solr/blob/trunk/lucene/analysis/common/src/java/org/apache/lucene/analysis/it/ItalianAnalyzer.java#L49
         */
        'it-elision' => [
            'type' => 'elision',
            'articles' => [
                'c', 'l', 'all', 'dall', 'dell', 'nell', 'sull', 'coll', 'pell',
                'gl', 'agl', 'dagl', 'degl', 'negl', 'sugl', 'un', 'm', 't', 's', 'v', 'd'
            ]
        ],

        /*
         * Filtre it-stop : supprime les mots vides italiens.
         *
         * @see https://github.com/apache/lucene-solr/blob/trunk/lucene/analysis/common/src/resources/org/apache/lucene/analysis/snowball/italian_stop.txt
         */
        'it-stop' => [
            'type' => 'stop',
            'stopwords' => ['_italian_']
        ],

        /*
         * Filtre it-stem : stemmer italien standard (snowball, Martin Porter)
         *
         * @see http://snowball.tartarus.org/texts/introduction.html
         * @see http://snowball.tartarus.org/algorithms/italian/stemmer.html
         * @see http://www.lirmm.fr/~mroche/Recherche/Articles/Porter/porter.pdf
         */
        'it-stem' => [
            'type' => 'stemmer',
            'name' => 'italian'
        ],

        /*
         * Filtre it-stem-light : stemmer italien light (Jacques Savoy)
         *
         * Moins aggressif que stem.
         *
         * @see http://www.ercim.eu/publication/ws-proceedings/CLEF2/savoy.pdf
         */
        'it-stem-light' => [
            'type' => 'stemmer',
            'name' => 'light_italian'
        ],

        // Remarque : le stemmer "minimal_italian" n'existe pas dans ES.
    ],

    /* --------------------------------------------------------------
     * analyzer : analyseurs pré-définis
     * -------------------------------------------------------------- */
    'analyzer' => [
        /*
         * it-text : permet une recherche "plein texte" sur le contenu
         * d'un champ en italien (titre, résumé...) en appliquant le
         * stemmer "it-stem-light".
         */
        'it-text' => [
            'type' => 'custom',

            'char_filter' => [
                'html_strip'    // Supprime les tags html
            ],

            'filter' => [
                'lowercase',    // Convertit le texte en minuscules
                'it-elision',   // Supprime les élisions (c', d', l'...)
                'it-stop',      // Supprime les mots-vides
                'asciifolding', // Supprime les accents
                'it-stem-light' // Stemming léger
            ],

            'tokenizer' => 'standard',
        ],
        // Remarques :
        // - il y a des accents en italien, donc asciifolding
        // - elision doit ête exécuté après lowercase (enlève que les minus)
        // - stop doit être exécuté après lowercase car les mots vides sont en
        //   minuscule dans la liste, mais avant asciifolding car ils peuvent
        //   avoir des accents.
    ],
];