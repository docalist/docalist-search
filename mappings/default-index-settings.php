<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2012, 2013 Daniel Ménard
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
 * Settings par défaut utilisés lorsqu'un index Elastic Search est créé.
 *
 * @return array
 */
return [
    '_meta' => ['docalist-search' =>  '0.1'],
    'settings' => [
        'analysis' => [

            /*
             * Liste des "token filters" pré-définis.
             */
            'filter' => [

                /*
                 * Paramètre le token filter "elision" en définissant
                 * explicitement la liste des articles supprimés.
                 */
                'elision' => [
                    'type' => 'elision',
                    'articles' => ['l', 'm', 't', 'qu', 'n', 's', 'j', 'd']
                ],

                /*
                 * Stemmer français standard (snowball, Martin Porter)
                 *
                 * @see http://snowball.tartarus.org/texts/introduction.html
                 * @see http://snowball.tartarus.org/algorithms/french/stemmer.html
                 * @see http://www.lirmm.fr/~mroche/Recherche/Articles/Porter/porter.pdf
                 */
                'stem-french' => [
                    'type' => 'stemmer',
                    'name' => 'french'
                ],

                /*
                 * Stemmer français light (Jacques Savoy)
                 *
                 * Moins aggressif que snowball.
                 *
                 * @see https://doc.rero.ch/record/13225/files/Savoy_Jacques_-_Light_Stemming_Approaches_fo_the_French_20091216.pdf
                 * @see http://members.unine.ch/jacques.savoy/clef/frenchStemmerPlus.txt
                 * @see org.apache.lucene.analysis.fr.FrenchLightStemmer.java
                 */
                'light-stem-french' => [
                    'type' => 'stemmer',
                    'name' => 'light_french'
                ],

                /*
                 * Stemmer français minimal ((Jacques Savoy)
                 *
                 * Encore moins affresif que light_french.
                 *
                 * @see http://members.unine.ch/jacques.savoy/papers/frjasis.pdf
                 * @see http://members.unine.ch/jacques.savoy/clef/frenchStemmer.txt
                 */
                'minimal-stem-french' => [
                    'type' => 'stemmer',
                    'name' => 'minimal_french'
                ],

            ],

            /*
             * Liste des analyseurs pré-définis
             */
            'analyzer' => [

                /*
                 * Analyseur par défaut : permet une recherche "plein
                 * text" sur le contenu d'un champ (titre, résumé...) :
                 *
                 * - Supprime les tags html
                 * - Convertit le texte en minuscules
                 * - Supprime les accents (folding)
                 * - Supprime les élisions (c', d', l'...)
                 * - Tokenisation standard
                 */
                'text' => [
                    'type' => 'custom',
                    'char_filter' => ['html_strip'],
                    'filter' => ['lowercase', 'asciifolding', 'elision'],
                    'tokenizer' => 'standard',
                ],

                /*
                 * Analyseur "keyword" : indexation d'un champ sur table
                 * (par exemple type de document, titre de périodique...)
                 *
                 * - Convertit le texte en minuscules
                 * - Supprime les accents (folding)
                 * - Supprime les élisions (c', d', l'...)
                 * - Pas de tokenisation (un article = un token)
                 */

                /*
                 En fait, pas utile : autant mettre le champ en "not_analyzed"
                'keyword' => [
                    'type' => 'custom',
                    'char_filter' => ['html_strip'],
                    'tokenizer' => 'keyword',
                    'filter' => ['lowercase', 'asciifolding', 'elision'],
                ],
                */
            ],
        ],
    ],
];