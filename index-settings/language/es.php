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
 * Analyseurs et filtres spécifiques à l'espagnol.
 *
 * Inspiré de :
 * http://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-lang-analyzer.html#spanish-analyzer
 *
 * @return array
 */
return [
    /* --------------------------------------------------------------
     * filter : traitement des tokens
     * -------------------------------------------------------------- */
    'filter' => [

        /*
         * Filtre es-stop : supprime les mots vides espagnols.
         *
         * @see https://github.com/apache/lucene-solr/blob/trunk/lucene/analysis/common/src/resources/org/apache/lucene/analysis/snowball/spanish_stop.txt
         */
        'es-stop' => [
            'type' => 'stop',
            'stopwords' => ['_spanish_']
        ],

        /*
         * Filtre es-stem : stemmer espagnol standard (snowball, Martin Porter)
         *
         * @see http://snowball.tartarus.org/texts/introduction.html
         * @see http://snowball.tartarus.org/algorithms/spanish/stemmer.html
         * @see http://www.lirmm.fr/~mroche/Recherche/Articles/Porter/porter.pdf
         */
        'es-stem' => [
            'type' => 'stemmer',
            'name' => 'spanish'
        ],

        /*
         * Filtre es-stem-light : stemmer espagnol light (Jacques Savoy)
         *
         * Moins aggressif que stem.
         *
         * http://clef.isti.cnr.it/2003/WN_web/22.pdf
         */
        'es-stem-light' => [
            'type' => 'stemmer',
            'name' => 'light_spanish'
        ],

        // Remarque : le stemmer "minimal_spanish" n'existe pas dans ES.
    ],

    /* --------------------------------------------------------------
     * analyzer : analyseurs pré-définis
     * -------------------------------------------------------------- */
    'analyzer' => [
        /*
         * es-text : permet une recherche "plein texte" sur le contenu
         * d'un champ en espagnol (titre, résumé...) en appliquant le
         * stemmer "es-stem-light".
         */
        'es-text' => [
            'type' => 'custom',

            'char_filter' => [
                'html_strip'    // Supprime les tags html
            ],

            'filter' => [
                'lowercase',    // Convertit le texte en minuscules
                'es-stop',      // Supprime les mots-vides
                'asciifolding', // Supprime les accents
                'es-stem-light' // Stemming léger
            ],

            'tokenizer' => 'standard',
        ],
        // Remarques :
        // - il y a des accents en espagnol, donc asciifolding
        // - stop doit être exécuté après lowercase car les mots vides sont en
        //   minuscule dans la liste, mais avant asciifolding car ils peuvent
        //   avoir des accents.
    ],
];