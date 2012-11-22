<?php
/**
 * This file is part of the "Docalist Core" plugin.
 * 
 * Copyright (C) 2012 Daniel Ménard
 * 
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Plugin Name: Docalist Core
 * Plugin URI:  http://docalist.org
 * Plugin Type: Piklist
 * Description: Docalist: socle de base.
 * Version:     0.2.1
 * Author:      Daniel Ménard
 * Author URI:  http://docalist.org/
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

// Initialise le framework
require_once __DIR__ . '/class/Docalist.php';
Docalist::initialize();

// Enregistre notre espace de noms
Docalist::registerNamespace(__NAMESPACE__, __DIR__ . '/class');

// Charge le plugin
Docalist::load('Docalist\\Core\\Plugin', __FILE__);
