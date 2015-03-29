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
 * Settings par défaut utilisés lorsqu'un index Elastic Search est créé.
 *
 * @return array
 */
return [
    'settings' => [
        'analysis' => array_merge_recursive(
            require __DIR__ . '/_template.php',
            require __DIR__ . '/language-independent.php',
            require __DIR__ . '/language/de.php',
            require __DIR__ . '/language/en.php',
            require __DIR__ . '/language/es.php',
            require __DIR__ . '/language/fr.php',
            require __DIR__ . '/language/it.php'
        )
    ],
];