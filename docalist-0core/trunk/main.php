<?php
/**
 * This file is part of the "Docalist Core" plugin.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Plugin Name: Docalist Core
 * Plugin URI:  http://docalist.org
 * Plugin Type: Piklist
 * Description: Docalist: core functionality and utilities for other Docalist plugins.
 * Version:     0.1
 * Author:      Docalist
 * Author URI:  http://docalist.org
 * Text Domain: docalist-core
 * Domain Path: /languages
 *  
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */

namespace Docalist;

// Charge et initialise le PluginManager
// Comme l'autoload n'est pas encore en place, on le charge "manuellement".
require_once __DIR__ . '/class/PluginManager.php';
PluginManager::initialize();

// Demande au PluginManager de nous charger nous-même comme plugin Docalist
PluginManager::load('Docalist\\Core\\Plugin', __DIR__);