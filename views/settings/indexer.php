<?php
/**
 * This file is part of the 'Docalist Search' plugin.
 *
 * Copyright (C) 2012, 2013 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     $Id$
 */
namespace Docalist\Search\Views;

use Docalist\Search\IndexerSettings;
use Docalist\Forms\Form;

/**
 * Paramètres de l'indexeur.
 *
 * @param IndexerSettings $settings Les paramètres de l'indexeur.
 * @param string $error Erreur éventuelle à afficher.
 * @param string[] $types Liste des types disponibles.
 */
?>
<div class="wrap">
    <?= screen_icon() ?>
    <h2><?= __("Paramètres de l'indexeur", 'docalist-search') ?></h2>

    <p class="description">
        <?= __('Utilisez le formulaire ci-dessous pour modifier les paramètres :', 'docalist-search') ?>
    </p>

    <?php if ($error) :?>
        <div class="error">
            <p><?= $error ?></p>
        </div>
    <?php endif ?>

    <?php
        $form = new Form();
        $form->checklist('types')->options($types);
        $form->input('bulkMaxSize');
        $form->input('bulkMaxCount');
        $form->submit(__('Enregistrer les modifications', 'docalist-search'));

        $form->bind($settings)->render('wordpress');
    ?>
</div>