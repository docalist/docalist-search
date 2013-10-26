<?php
/**
 * This file is part of the 'Docalist Core' plugin.
 *
 * Copyright (C) 2012, 2013 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Views
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     $Id$
 */
namespace Docalist\Views;

/**
 * Génère une page "Bad Request".
 *
 * @param string $message Le message à afficher.
 */

$title = __('Requête incorrecte', 'docalist-core');
?>
<!DOCTYPE html>
<html>
    <head>
        <title><?= $title ?></title>
    </head>
    <body>
        <h1><?= $title ?></h1>
        <p><?= $message ?></p>
    </body>
</html>