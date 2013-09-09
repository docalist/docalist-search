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

/*

A revoir dans une nouvelle version.
Objectifs :
- Se débarrasser des classes statiques.
- Etre sur que docalist-core est chargé en premier, sans avoir le truc du 0-core
- Que les plugins docalist ne plantent pas si docalist core est désactivé

Principe :
- wordpress charge tous les plugins
- ceux-ci ne font rien : ils se contentent d'installer un filtre wordpress qui
  sera appellé quand docalist core sera chargé

Dans docalist core :
namespace Docalist\Core;

add_action('plugins_loaded', function() {
    // Initialise l'autoloader
    require_once __DIR__ . '/class/Autoloader.php';
    $autoloader = new Autoloader();

    // Enregistre notre espace de nom
    $autoloader->register(__NAMESPACE__, __DIR__ . '/class');

    // Charge le plugin
    $docalist = new Plugin();

    do_action('docalist_loaded', $docalist);
});

Eventuellement, stocker $docalist dans une var globale ?
Et avoir une fonction générique :
function docalist($key) {
    global $docalist;

    return $key ? $docalist->get($key) : $docalist;
}

Dans un plugin :

add_action('docalist_loaded', function(Docalist\Core\Plugin $docalist) {
    // Enregistre notre espace de noms
    $docalist->autoload(__NAMESPACE__, __DIR__ . '/class');

    // Charge le plugin
    $docalist->add(new Plugin)
});

// $docalist->autoload(xxx) est juste un raccourci vers
// $docalist->autoloader()->register(xxx)

*/