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
 */

/**
 * Analyseurs indépendants de la langue.
 *
 * @return array
 */
return [

    /* --------------------------------------------------------------
     * char_filter : traitement des caractères
     * -------------------------------------------------------------- */
    'char_filter' => [
        'url_remove_protocol' => [
            'type' => 'pattern_replace',
            'pattern' => '^[a-zA-Z]+:/*',
            'replacement' => '',
        ],
        'url_remove_www' => [
            'type' => 'pattern_replace',
            'pattern' => '^www\.?',
            'replacement' => '',
        ],
        'url_normalize_sep' => [
            'type' => 'pattern_replace',
            'pattern' => '[/\\\\#@:]+',
            'replacement' => '/',
        ],
    ],

    /* --------------------------------------------------------------
     * tokenizers : découpage du texte en tokens
     * -------------------------------------------------------------- */
    'tokenizer' => [
        'url_tokenizer' => [
            'type' => 'keyword',
        ],
    ],

    /* --------------------------------------------------------------
     * filter : traitement des tokens
     * -------------------------------------------------------------- */
    'filter' => [

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
                'html_strip',   // Supprime les tags html
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
            'char_filter' => [
                'url_remove_protocol',
                'url_remove_www',
                'url_normalize_sep',
            ],
            'filter' => [
                'lowercase',    // Convertit le texte en minuscules
                'asciifolding', // Supprime les accents
            ],
            'tokenizer' => 'url_tokenizer',
        ],

        /*
         * hierarchy : permet d'indexer le path d'un tag dans une hiérarchie.
         */
        'hierarchy' => [
            'type' => 'custom',

            'filter' => [
                'lowercase',    // Convertit le texte en minuscules
                'asciifolding', // Supprime les accents
            ],
            'tokenizer' => 'path_hierarchy',
        ],
    ],
];

