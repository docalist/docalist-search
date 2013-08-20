<?php
// Environnement de test
$GLOBALS['wp_tests_options'] = array(
    'active_plugins' => array(
        'docalist-0core/docalist-core.php'
    ),
);

// The path to wordpress-tests
require_once 'D:/web/wordpress-tests/includes/bootstrap.php';
// @todo : ne pas fixer le path en dur