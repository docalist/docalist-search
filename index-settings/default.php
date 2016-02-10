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
 * Settings par défaut utilisés lorsqu'un index Elastic Search est créé.
 *
 * @return array
 */
return [
    'settings' => [
        /*
         * Paramétres généraux de l'index
         *
         * @see https://www.elastic.co/guide/en/elasticsearch/reference/master/indices-create-index.html
         * @see https://www.elastic.co/guide/en/elasticsearch/reference/master/index-modules.html
         */
        'index' => [
            // Valeurs par défaut, surchargées par Indexer::getIndexSettings() à partir des settings
            'number_of_shards' => 5,
            'number_of_replicas' => 1,

            // Autres settings
            'refresh_interval' => '1s',
            'max_result_window' => 100000,
            'ttl.disable_purge' => true
        ],

        /*
         * Analyseurs prédéfinis
         *
         * @see https://www.elastic.co/guide/en/elasticsearch/reference/master/analysis.html
         */
        'analysis' => array_merge_recursive(
            require __DIR__ . '/_template.php',
            require __DIR__ . '/language-independent.php',
            require __DIR__ . '/language/de.php',
            require __DIR__ . '/language/en.php',
            require __DIR__ . '/language/es.php',
            require __DIR__ . '/language/fr.php',
            require __DIR__ . '/language/it.php'
        ),

        /*
         * Mappings
         *
         * @see https://www.elastic.co/guide/en/elasticsearch/reference/master/mapping.html
         */
        'mappings' => [],
    ],
];
