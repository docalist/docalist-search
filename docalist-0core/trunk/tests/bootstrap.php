<?php
// Environnement de test
$GLOBALS['wp_tests_options'] = array(
    'active_plugins' => array(
        'docalist-0core/docalist-core.php'
    ),
);

// wordpress-tests doit être dans le include_path de php
// sinon, modifier le chemin d'accès ci-dessous
require_once 'wordpress-tests/includes/bootstrap.php';
