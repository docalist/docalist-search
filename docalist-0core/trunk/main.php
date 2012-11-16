<?php
/**
 * This file is part of the "Docalist Core" plugin.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Plugin Name: Docalist Core
 * Plugin URI:  http://docalist.org
 * Description: Docalist : socle de base pour les autres plugins Docalist.
 * Version:     0.1
 * Author:      Docalist
 * Author URI:  http://docalist.org
 *
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */

namespace Docalist;
use \Exception;

require_once __DIR__ . '/class/PluginManager.php';
PluginManager::initialize();
