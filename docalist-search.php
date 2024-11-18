<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2020 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 *
 * Plugin Name: Docalist Search
 * Plugin URI:  https://docalist.org
 * Description: An ElasticSearch-based search engine for WordPress.
 * Version:     4.2.0
 * Author:      Daniel Ménard
 * Author URI:  https://docalist.org/
 * Text Domain: docalist-search
 * Domain Path: /languages
 *
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
declare(strict_types=1);

namespace Docalist\Search;
use Docalist\Autoloader;

/**
 * Version du plugin.
 */
define('DOCALIST_SEARCH_VERSION', '4.2.0'); // Garder synchro avec la version indiquée dans l'entête

/**
 * Path absolu du répertoire dans lequel le plugin est installé.
 *
 * Par défaut, on utilise la constante magique __DIR__ qui retourne le path réel du répertoire et résoud les liens
 * symboliques.
 *
 * Si le répertoire du plugin est un lien symbolique, la constante doit être définie manuellement dans le fichier
 * wp_config.php et pointer sur le lien symbolique et non sur le répertoire réel.
 */
!defined('DOCALIST_SEARCH_DIR') && define('DOCALIST_SEARCH_DIR', __DIR__);

/**
 * Path absolu du fichier principal du plugin.
 */
define('DOCALIST_SEARCH', DOCALIST_SEARCH_DIR . DIRECTORY_SEPARATOR . basename(__FILE__));

/**
 * Url de base du plugin.
 */
define('DOCALIST_SEARCH_URL', plugins_url('', DOCALIST_SEARCH));

/**
 * Initialise le plugin.
 */
add_action('plugins_loaded', function () {
    // Auto désactivation si les plugins dont on a besoin ne sont pas activés
    $dependencies = ['DOCALIST_CORE'];
    foreach ($dependencies as $dependency) {
        if (! defined($dependency)) {
            add_action('admin_notices', function () use ($dependency) {
                deactivate_plugins(DOCALIST_SEARCH);
                unset($_GET['activate']); // empêche wp d'afficher "extension activée"
                printf(
                    '<div class="%s"><p><b>%s</b> has been deactivated because it requires <b>%s</b>.</p></div>',
                    'notice notice-error is-dismissible',
                    'Docalist Search',
                    ucwords(strtolower(strtr($dependency, '_', ' ')))
                );
            });
            return;
        }
    }

    // Ok
    docalist(Autoloader::class)
        ->add(__NAMESPACE__, __DIR__ . '/class')
        ->add(__NAMESPACE__ . '\Tests', __DIR__ . '/tests');

    // docalist(Services::class)
    //     ->set(Plugin::class, new Plugin())
    //     ->deprecate('docalist-search', Plugin::class, '2023-11-27');
    docalist(DocalistSearchPlugin::class)->initialize();
// var_dump(docalist(ElasticSearchClient::class));
// exit;
});
