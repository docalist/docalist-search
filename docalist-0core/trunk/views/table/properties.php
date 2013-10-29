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
 * Modifie les propriétés d'une table d'autorité.
 *
 * @param string $tableName Nom de la table à modifier.
 * @param TableInfo $tableInfo Infos sur la table.
 * @param string $error Message d'erreur éventuel à afficher.
 */

use Docalist\Table\TableInfo;
use Docalist\Forms\Form;
?>
<div class="wrap">
    <?= screen_icon() ?>
    <h2><?= sprintf(__('%s : propriétés', 'docalist-core'), $tableInfo->label ?: $tableName) ?></h2>

    <p class="description">
        <?= __('Utilisez le formulaire ci-dessous pour modifier les propriétés de la table.', 'docalist-core') ?>
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

        $form->div('type')->tag('span.description', $tableInfo->type);
        $form->div('path')->tag('span.description', $tableInfo->path);

        $form->submit(__('Enregistrer les modifications', 'docalist-search'));

        $form->bind($tableInfo)->render('wordpress');
    ?>
</div>