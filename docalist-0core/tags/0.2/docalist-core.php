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
 * Description: Docalist: socle de base.
 * Version:     0.2
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

namespace Docalist\Core;
use Docalist;

// Charge et initialise la classe principale de Docalist
// Comme l'autoload n'est pas encore en place, on la charge "manuellement".
require_once __DIR__ . '/class/Docalist.php';
Docalist::initialize();

// Demande à Docalist de nous charger nous-même comme plugin Docalist
Docalist::load('Docalist\\Core\\Plugin', __DIR__);