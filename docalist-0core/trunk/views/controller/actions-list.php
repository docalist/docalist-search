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
 * Liste les actions disponibles dans un contrôleur.
 *
 * @param string $title Titre de la page
 * @param string[] $actions Liste des actions à affficher.
 */
?>
<div class="wrap">
    <?= screen_icon() ?>
    <h2><?= $title ?></h2>

    <?php if (empty($actions)): ?>
        <p><?= __("Aucune action n'est disponible dans ce module.", 'docalist-core') ?></p>
    <?php else: ?>
        <ul class="ul-disc">
        <?php foreach($actions as $action): ?>
            <li>
                <h3>
                    <a href="<?= $this->url($action) ?>"><?= $action ?></a>
                </h3>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>

</div>