<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2020 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Search\Views;

use Docalist\Search\SettingsPage;
use Docalist\Search\Settings;
use Docalist\Forms\Form;

/**
 * Paramètres de la recherche.
 *
 * @var SettingsPage    $this
 * @var Settings        $settings           Les paramètres de l'indexeur.
 * @var array           $fields             Un tableau de la forme champ => libellé listant les attributs
 *                                          de recherche disponibles.
 * @var string          $error              Erreur éventuelle à afficher.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
?>
<div class="wrap">
    <h1><?= __("Paramètres du moteur de recherche.", 'docalist-search') ?></h1>

    <p class="description"><?=
        __(
            "Les options ci-dessous ne doivent être activées qu'une fois que vous
            avez choisi les contenus à indexer et lancé une réindexation manuelle
            de l'ensemble de tous les documents.",
            'docalist-search'
        );
    ?></p><?php

    if ($error) { ?>
        <div class="error">
            <p><?= $error ?></p>
        </div><?php
    }

    $form = new Form();
    $form->select('searchpage')->setOptions(pagesList())->setFirstOption(false);

    $defaultSearchFields = $form->table('defaultSearchFields')
        ->setRepeatable();

    $defaultSearchFields
        ->entryPicker('field')
        ->setOptions($fields);

    $defaultSearchFields
        ->input('weight')
        ->setAttribute('type', 'number')
        ->setAttribute('min', '1');

    $form->submit(__('Enregistrer les modifications', 'docalist-search'))->addClass('button button-primary');

    $form->bind($settings);
    $form->display(); ?>
</div>

<?php
/**
 * Retourne la liste hiérarchique des pages sous la forme d'un tableau
 * utilisable dans un select.
 *
 * @return array Un tableau de la forme PageID => PageTitle
 */
function pagesList()
{
    $pages = ['…'];
    foreach (get_pages() as $page) { /* @var \WP_Post $page */
        $pages[$page->ID] = str_repeat('   ', count($page->ancestors)) . $page->post_title;
    }

    return $pages;
}
