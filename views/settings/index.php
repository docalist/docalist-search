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

/**
 * Page d'accueil.
 *
 */
?>
<div class="wrap">
    <?= screen_icon() ?>
    <h2><?= __("Paramètres de Docalist Search", 'docalist-search') ?></h2>

    <p class="description"><?php
        //@formatter:off
        echo __(
            'Docalist Search est un plugin qui permet de doter votre site
            WordPress d\'un moteur de recherche moderne et performant.
            Utilisez les liens ci-dessous pour paramétrer votre moteur.',
            'docalist-search'
        );
        // @formatter:on
    ?></p>

    <h3>
        <a href="<?= esc_url($this->url('ServerSettings')) ?>">
            <?= __('Paramètres du serveur', 'docalist-search') ?>
        </a>
    </h3>
    <p class="description">
        <?= __('Serveur et index ElasticSearch à utiliser, timeout des requêtes.', 'docalist-search') ?>
    </p>


    <h3>
        <a href="<?= esc_url($this->url('IndexerSettings')) ?>">
            <?= __("Paramètres de l'indexeur", 'docalist-search') ?>
        </a>
    </h3>
    <p class="description">
        <?= __("Contenus à indexer et options d'indexation.", 'docalist-search') ?>
    </p>


    <h3>
        <a href="<?= esc_url($this->url('SearchSettings')) ?>">
            <?= __("Paramètres de recherche", 'docalist-search') ?>
        </a>
    </h3>
    <p class="description">
        <?= __("Activer/désactiver la recherche avec Docalist Search.", 'docalist-search') ?>
    </p>


    <h3>
        <a href="<?= esc_url($this->url('Reindex')) ?>">
            <?= __("Réindexation manuelle", 'docalist-search') ?>
        </a>
    </h3>
    <p class="description">
        <?= __("Permet de lancer une réindexation manuelle des contenus.", 'docalist-search') ?>
    </p>


    <h3>
        <a href="<?= esc_url($this->url('Status')) ?>">
            <?= __("Statut", 'docalist-search') ?>
        </a>
    </h3>
    <p class="description">
        <?= __("Informations et statistiques sur le serveur et l'index ElasticSearch.", 'docalist-search') ?>
    </p>

</div>