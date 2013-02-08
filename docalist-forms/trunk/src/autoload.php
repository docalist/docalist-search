<?php
/**
 * This file is part of the "Docalist Forms" package.
 *
 * Copyright (C) 2012,2013 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Forms
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */

namespace Docalist\Forms;

/**
 * Un autoloader minimal qui ne sait charger que les classes de notre package.
 *
 * Si votre application a déjà un autoloader, utilisez-le plutôt que de charger
 * ce fichier.
 */
spl_autoload_register(function($class){
    if (strncmp(__NAMESPACE__, $class, $len = strlen(__NAMESPACE__)) === 0) {
        require __DIR__ . '/class/' . substr($class, $len + 1) . '.php';
    }
});