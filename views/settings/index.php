<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Search\Views;

use Docalist\Search\SettingsPage;

/**
 * Page d'accueil.
 *
 * @var SettingsPage $this
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
?>
<div class="wrap">
    <h1><?= __("Réglages de Docalist Search", 'docalist-search') ?></h1>

    <p class="description"><?php
        echo __(
            "Docalist Search est un plugin qui permet de doter votre site WordPress d'un moteur de recherche moderne
             et performant. Utilisez les liens ci-dessous pour paramétrer votre moteur.",
            'docalist-search'
        );
    ?></p>

    <h2>
        <a href="<?= esc_url($this->getUrl('ServerSettings')) ?>">
            <?= __('Paramètres Docalist-Search', 'docalist-search') ?>
        </a>
    </h2>
    <p class="description">
        <?= __(
                "Serveur et index ElasticSearch à utiliser, timeout des requêtes et paramètres d'indexation.",
                'docalist-search'
            )
        ?>
    </p>


    <h2>
        <a href="<?= esc_url($this->getUrl('CreateIndex')) ?>">
            <?= __("Créer l'index de recherche", 'docalist-search') ?>
        </a>
    </h2>
    <p class="description">
        <?= __('Choix des contenus à indexer et indexation initiale des contenus existants.', 'docalist-search') ?>
    </p>


    <h2>
        <a href="<?= esc_url($this->getUrl('SearchSettings')) ?>">
            <?= __("Paramètres du moteur de recherche", 'docalist-search') ?>
        </a>
    </h2>
    <p class="description">
        <?= __("Choix de la page des réponses et champs de recherche par défaut.", 'docalist-search') ?>
    </p>

    <h2 style="margin-top: 5em;">
        <?= __("Outils de débogage", 'docalist-search') ?>
    </h2>

    <ul class="ul-square">
        <li>
            <a href="<?= esc_url($this->getUrl('FieldData')) ?>">
                <?= __("FieldData : affiche le contenu exact des documents stockés dans l'index", 'docalist-search') ?>
            </a>
        </li>
    </ul>


<?php /*
    <h2>
        <a href="<?= esc_url($this->getUrl('ServerStatus')) ?>">
            <?= __("Statut", 'docalist-search') ?>
        </a>
    </h2>
    <p class="description">
        <?= __("Informations et statistiques sur le serveur et l'index ElasticSearch.", 'docalist-search') ?>
    </p>
*/ ?>
</div>
