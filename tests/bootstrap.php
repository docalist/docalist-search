<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Search\Tests;

// Environnement de test
$GLOBALS['wp_tests_options'] = array(
    'active_plugins' => array(
        'docalist-core/docalist-core.php',
        'docalist-search/docalist-search.php',
    ),
);

// wordpress-tests doit être dans le include_path de php
// sinon, modifier le chemin d'accès ci-dessous
require_once 'wordpress-develop/tests/phpunit/includes/bootstrap.php';
