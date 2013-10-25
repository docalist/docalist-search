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
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     $Id$
 */
namespace Docalist;

/**
 * Génère une page "Action non trouvée".
 *
 * @param string $action Le nom de l'action qui n'a pas été trouvée.
 */

$title = __('Action non trouvée', 'docalist-core');
$message = __("L'action <code>%s</code> n'existe pas.", 'docalist-core');
$message = sprintf($message, $action);
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