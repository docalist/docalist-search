<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2012,2013 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Plugin Name: Docalist Search
 * Plugin URI:  http://docalist.org
 * Description: Docalist Search Plugin.
 * Version:     0.1
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
use Docalist, Docalist\Autoloader;

if (class_exists('Docalist')) {
    // Enregistre notre espace de noms
    Autoloader::register(__NAMESPACE__, __DIR__ . '/class');

    // Charge le plugin
    Docalist::load('Docalist\Search\Plugin', __FILE__);
}