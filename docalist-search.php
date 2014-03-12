<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2012-2014 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Plugin Name: Docalist Search
 * Plugin URI:  http://docalist.org
 * Description: Docalist Search Plugin.
 * Version:     0.2
 * Author:      Daniel Ménard
 * Author URI:  http://docalist.org/
 * Text Domain: docalist-search
 * Domain Path: /languages
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */
namespace Docalist\Search;

/**
 * Affiche une erreur dans le back-office si Docalist Core n'est pas activé.
 */
add_action('admin_notices', function() {
    if (! function_exists('docalist')) {
        echo '<div class="error"><p>Docalist Search requires Docalist Core.</p></div>';
    }
});

/**
 * Initialise notre plugin une fois que Docalist Core est chargé.
 */
add_action('docalist_loaded', function () {
    docalist('autoloader')->add(__NAMESPACE__, __DIR__ . '/class');
    docalist('services')->add('docalist-search', new Plugin());
});