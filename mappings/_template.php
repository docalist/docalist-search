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
 * Template : ce fichier est un modèle à recopier pour créer de nouveaux
 * analyseurs spécifiques à une langue.
 *
 * Il est inclus dans default.php et sert juste à fixer l'ordre des sections
 * lors de la fusion des tableaux.
 *
 * @return array
 */
return [
    // Le tableau retourné est fusionné au niveau index/settings/analysis
    // cf. http://www.elastic.co/guide/en/elasticsearch/guide/current/custom-analyzers.html

    /* --------------------------------------------------------------
     * char_filter : traitement des caractères
     * -------------------------------------------------------------- */
    'char_filter' => [

    ],


    /* --------------------------------------------------------------
     * tokenizers : découpage du texte en tokens
     * -------------------------------------------------------------- */
    'tokenizer' => [

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

    ],
];