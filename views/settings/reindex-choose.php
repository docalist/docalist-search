<?php
/**
 * This file is part of the 'Docalist Search' plugin.
 *
 * Copyright (C) 2012-2014 Daniel Ménard
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

use Docalist\Forms\Form;

/**
 * Permet à l'utilisateur de choisir les types à réindexer.
 *
 * @param array $types Un tableau contenant la liste des types actuellement
 * indexés, sous la forme nom du type => libellé.
 */
?>
<div class="wrap">
    <?= screen_icon() ?>
    <h2><?= __("Réindexation manuelle", 'docalist-search') ?></h2>

    <p class="description"><?php
        //@formatter:off
        printf(
            __(
                'Cette page vous permet de lancer une réindexation manuelle
                des <a href="%s">contenus de votre base</a> et de regénérer
                les index du moteur de recherche.
                Choisissez les types de contenus à réindexer puis cliquez
                sur le bouton du formulaire ci-dessous.',
                'docalist-search'
            ),
            esc_url($this->url('IndexerSettings'))
        );
        // @formatter:on
    ?></p>

    <?php if (empty($types)) :?>
        <div class="error"><p><?php
            //@formatter:off
            printf(
                __(
                    'Vous n\'avez pas encore choisi les contenus à indexer
                    dans votre moteur de recherche. <br />Allez sur la page
                    <a href="%s">paramètres de l\'indexeur</a> et choisissez
                    au moins un type de contenu à indexer.',
                    'docalist-search'
                ),
                esc_url($this->url('IndexerSettings'))
            );
            // @formatter:on
        ?></p></div>
    <?php else :
        //@formatter:off
        $form = new Form();
        $form->checklist('selected')
             ->label(__('Choisissez les types à réindexer', 'docalist-search'))
             ->options($types);
        $form->submit(__('Réindexer les types sélectionnés', 'docalist-search'));
        // @formatter:on

        $form->render('wordpress');
    endif; ?>
</div>