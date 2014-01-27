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

// pas de namespace, la fonction docalist() est globale.

/**
 * Retourne l'instance du plugin Docalist Core ou un service de Docalist si
 * un paramètre est passé.
 *
 * @param string $service L'identifiant du service à retourner ou null pour
 * obtenir l'instance de Docalist.
 *
 * @return Docalist\Core\Plugin
 */
function docalist($service = null) {
    static $docalist;

    // Au premier appel, on initialise l'instance
    if (is_null($docalist)) {
        // Initialise l'autoloader
        require_once __DIR__ . '/class/Autoloader.php';
        $autoloader = new Docalist\Autoloader([
            'Docalist' => __DIR__ . '/class',
            'Docalist\Forms' => __DIR__ . '/lib/docalist-forms/class',
            'Symfony' => __DIR__ . '/lib/Symfony'
        ]);

        // Initialise le plugin
        $docalist = new Docalist\Core\Plugin();
        $docalist->add('autoloader', $autoloader);

        // La classe Docalist est un alias global de Docalist\Core\Plugin
        class_alias('Docalist\Core\Plugin', 'Docalist');
    }

    return $service ? $docalist->get($service) : $docalist;
}

/**
 * Charge les plugins Docalist
 */
add_action('plugins_loaded', function() {
    do_action('docalist_loaded', docalist());
});