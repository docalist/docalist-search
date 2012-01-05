<?php
require_once __DIR__.'/vendor/Symfony/Component/ClassLoader/UniversalClassLoader.php';

use Symfony\Component\ClassLoader\UniversalClassLoader;

$loader = new UniversalClassLoader();
$loader->registerNamespaces
(
    array
    (
        'Symfony'    => __DIR__ . '/vendor',
        'Fooltext'   => __DIR__ . '/src',
    )
);
/*
$loader->registerPrefixes(
    array
    (
        'Multimap' => __DIR__.'/src/Fooltext/',
    )
);
*/
$loader->register();

use Symfony\Component\ClassLoader\MapClassLoader;

$loader = new MapClassLoader
(
    array
    (
        'Xapian'         => __DIR__ . '/vendor/Xapian/xapian.php',
		'XapianDatabase' => __DIR__ . '/vendor/Xapian/xapian.php',
        'XapianDocument' => __DIR__ . '/vendor/Xapian/xapian.php',
        'XapianQuery'    => __DIR__ . '/vendor/Xapian/xapian.php',
		'Multimap'       => __DIR__ . '/src/Fooltext/Multimap.php',
    )
);

$loader->register();