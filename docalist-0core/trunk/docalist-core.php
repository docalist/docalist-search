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
 * Version:     0.2.2
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
use Docalist, Docalist\Autoloader;

// Initialise l'autoloader
require_once __DIR__ . '/class/Autoloader.php';

// Charge la classe de base du framework
require_once __DIR__ . '/class/Docalist.php';

// Enregistre notre espace de nom
Autoloader::register('Docalist', __DIR__ . '/class');

// Enregistre docalist-forms
Autoloader::register('Docalist\Forms', __DIR__ . '/lib/docalist-forms/class');

// Charge le plugin "Core"
Docalist::load('Docalist\Core', __FILE__);
