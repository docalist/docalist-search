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
 * Génère une page "Accès non autorisé".
 *
 * @param string $action Le nom de l'action exécutée.
 */

$title = __('Accès refusé', 'docalist-core');
$message = __("Désolé, mais vous n'avez pas les droits requis pour exécuter cette action.", 'docalist-core');
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