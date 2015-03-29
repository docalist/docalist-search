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
 * Analyseurs indépendants de la langue.
 *
 * @return array
 */
return [

    /* --------------------------------------------------------------
     * filter : traitement des tokens
     * -------------------------------------------------------------- */
    'filter' => [

        /*
         * url-stopwords : supprime les mots-vides dans les urls.
         */
        'url-stopwords' => [
            'type' => 'stop',
            'stopwords' => ['http', 'https', 'ftp', 'www']
        ]
    ],

    /* --------------------------------------------------------------
     * analyzer : analyseurs pré-définis
     * -------------------------------------------------------------- */
    'analyzer' => [

        /*
         * text : permet une recherche "plein texte" sur le contenu d'un
         * champ (titre, résumé...) sans tenir compte de la langue (pas
         * de stemming).
         */
        'text' => [
            'type' => 'custom',

            'char_filter' => [
                'html_strip'    // Supprime les tags html
            ],
            'filter' => [
                'lowercase',    // Convertit le texte en minuscules
                'asciifolding', // Supprime les accents
            ],
            'tokenizer' => 'standard',
        ],

        /*
         * suggest : analyseur pour les lookups (autocomplete).
         */
        'suggest' => [
            'type' => 'custom',
            'tokenizer' => 'keyword',
            'filter' => ['lowercase', 'asciifolding'],
        ],

        /*
         * url : analyseur pour les urls.
         *
         * @see http://stackoverflow.com/a/18980048
         */
        'url' => [
            'type' => 'custom',
            'tokenizer' => 'lowercase', // = Letter Tokenizer + Lowercase Filter
            'filter' => ['url-stopwords'],
        ],
    ],
];