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
 * Crée une nouvelle table par recopie d'une table existante.
 *
 * @param string $tableName Nom de la table à recopier.
 * @param TableInfo $tableInfo Infos sur la table à créer.
 * @param string $error Message d'erreur éventuel à afficher.
 */

use Docalist\Forms\Form;
use Docalist\Table\TableInfo;
?>
<div class="wrap">
    <?= screen_icon() ?>
    <h2><?= sprintf(__('Recopier la table "%s"', 'docalist-core'), $tableName) ?></h2>

    <p class="description">
        <?= __('Indiquez les paramètres de la nouvelle table :', 'docalist-core') ?>
    </p>

    <?php if ($error) :?>
        <div class="error">
            <p><?= $error ?></p>
        </div>
    <?php endif ?>

    <?php
        $form = new Form();
        $form->input('name')->attribute('class', 'regular-text');
        $form->input('label')->attribute('class', 'large-text');
        $form->checkbox('nodata')
             ->label(__('Structure uniquement', 'docalist-core'));
        $form->submit(__('Ok', 'docalist-search'));

        $form->bind($tableInfo)->render('wordpress');
    ?>
</div>