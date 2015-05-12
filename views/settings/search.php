<?php
/**
 * This file is part of the 'Docalist Search' plugin.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
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

use Docalist\Search\Settings;
use Docalist\Forms\Form;

/**
 * Paramètres de la recherche.
 *
 * @param Settings $settings Les paramètres de l'indexeur.
 * @param string $error Erreur éventuelle à afficher.
 */
?>
<div class="wrap">
    <?= screen_icon() ?>
    <h2><?= __("Activer la recherche et l'indexation en temps réel.", 'docalist-search') ?></h2>

    <p class="description"><?php
        //@formatter:off
        echo __(
            "Les options ci-dessous ne doivent être activées qu'une fois que vous
            avez choisi les contenus à indexer et lancé une réindexation manuelle
            de l'ensemble de tous les documents.",
            'docalist-search'
        );
        // @formatter:on
    ?></p>

    <?php if ($error) :?>
        <div class="error">
            <p><?= $error ?></p>
        </div>
    <?php endif ?>

    <?php
        $form = new Form();
        $form->select('searchpage')->options(pagesList())->firstOption(false);
        $form->checkbox('enabled');
        $form->submit(__('Enregistrer les modifications', 'docalist-search'));

        $form->bind($settings)->render('wordpress');
    ?>
</div>

<?php
/**
 * Retourne la liste hiérarchique des pages sous la forme d'un tableau
 * utilisable dans un select.
 *
 * @return array Un tableau de la forme PageID => PageTitle
 */
function pagesList() {
    $pages = ['…'];
    foreach(get_pages() as $page) { /* @var $page \WP_Post */
        $pages[$page->ID] = str_repeat('   ', count($page->ancestors)) . $page->post_title;
    }

    return $pages;
}
