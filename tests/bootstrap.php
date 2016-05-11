<?php
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
