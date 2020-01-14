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

/**
 * Cette vue est affichée lors de la création de l'index.
 *
 * Lorsqu'elle est exécutée, cette vue se contente d'installer des filtres qui seront appellés au bon moment
 * par le réindexeur au fur et à mesure de l'indexation.
 *
 * @var SettingsPage $this
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */

// Nombre de documents indexés pour un type donné
$total = 0;

/**
 * Entête de la page
 */?>
<div class="wrap">
    <h1><?= __("Création de l'index Elasticsearch", 'docalist-search') ?></h1>
    <p class="description"><?=
        __(
            "L'index Elasticsearch va être créé et les types de contenus que vous avez choisi vont être indexés.
             La page affichera des informations supplémentaires au fur et à mesure, veuillez patienter.",
            'docalist-search'
        ) ?>
    </p><?php

    /**
     * create_index : création de l'index Elasticsearch.
     */
    add_action('docalist_search_before_create_index', function (string $alias): void { ?>
        <h2><?= sprintf(__('Création du nouvel index "%s"', 'docalist-search'), $alias)?></h2>
        <ul class="ul-square">
            <li><?=
                __(
                    "Pendant l'indexation, l'ancien index (s'il existe) est toujours disponible pour la recherche.
                    Le nouvel index sera activé lorsque l'indexation sera terminée.",
                    'docalist-search'
                ) ?>
            </li>
        </ul><?php
        flush();
    });

    /**
     * before_reindex_type : début de la réindexation d'un type donné.
     *
     * Affiche un titre h3 et ouvre un <ul>.
     */
    add_action('docalist_search_before_reindex_type', function (string $type, string $label) use (& $total): void { ?>
        <h2><?= $label ?></h2>
        <p><?= __('Chargement des documents à indexer...', 'docalist-search') ?></p>
        <ul class="ul-square"><?php
        $total = 0;
        flush();
    }, 10, 2);

    /**
     * after_flush : le buffer d'indexation à été envoyé à elasticsearch.
     *
     * Affiche le temps pris par le flush et ferme le <li>.
     */
    add_action('docalist_search_after_flush', function (int $count, int $size, float $time) use (& $total): void { ?>
        <li><?php
        $total += $count;
        $msg =__('%d documents indexés, stockage dans elasticsearch (%s, %.1f sec.)', 'docalist-search');
        printf($msg, $total, size_format($size), $time); ?>
        </li><?php
        flush();
    }, 10, 3);

    /**
     * after_reindex_type : fin de la réindexation d'un type.
     *
     * Ferme le <ul> ouvert par docalist_search_before_reindex_type et indique le
     * nombre de documents indexés.
     */
    add_action('docalist_search_after_reindex_type', function (string $type, string $label, array $stats): void { ?>
        </ul><?php
        $msg =__('%s : %d documents indexés en %.1f secondes.', 'docalist-search');
        $msg = sprintf($msg, $label, $stats['index'], $stats['time']);
        printf('<p><strong>%s</strong></p>', $msg);
        flush();
    }, 10, 3);

    /**
     *  after_reindex : la réindexation est terminée.
     *
     *  Affiche une synthèse générale avec les stats fournies par l'indexeur.
     */
    add_action('docalist_search_after_reindex', function (array $types, array $stats): void { ?>
        <h2><?= __("Statistiques sur les contenus indexés", 'docalist-search') ?></h2>

        <table class="widefat fixed">
            <thead>
                <tr>
                    <th><?= __('Statistiques', 'docalist-search') ?></td>
                    <th><?= __('Documents indexés', 'docalist-search') ?></th>
                    <th><?= __('Taille moyenne', 'docalist-search') ?></th>
                    <th><?= __('Durée (en secondes)', 'docalist-search') ?></th>
                </tr>
            </thead>

            <tbody><?php
            $row = 0;
            foreach ($stats as $type => $stat) {
                $index = $stat['index'];
                $size = $index ? ($stat['size'] / $index) : 0;
                $time = $stat['time']; ?>

                <tr class="<?= $row % 2 ? '' : 'alternate' ?>">
                    <th><strong><?= $types[$type] ?></strong></th>
                    <td><?= $index ?></td>
                    <td><?= size_format($size, 1) ?></td>
                    <td><?= round($time, 1) ?></td>
                </tr><?php
                ++$row;
            } ?>
            </tbody><?php

            // Calcule les totaux
            $index = array_sum(array_column($stats, 'index'));
            $size = $index ? (array_sum(array_column($stats, 'size')) / $index) : 0;
            $time = array_sum(array_column($stats, 'time')); ?>

            <tfoot>
                <tr>
                    <th><?= __('Total', 'docalist-search') ?></td>
                    <th><?= $index ?></th>
                    <th><?= size_format($size, 1) ?></th>
                    <th><?= round($time, 1) ?></th>
                </tr>
            </tfoot>
        </table> <?php

        flush();
    }, 10, 2);


    /**
     * activate_index : création / maj des alias.
     */
    add_action('docalist_search_activate_index', function (string $alias): void { ?>
        <h2><?= __('Activation du nouvel index', 'docalist-search') ?></h2>
        <ul class="ul-square">
            <li><?=
                sprintf(
                    __('Le nouvel index "%s" sera désormais utilisé pour la recherche.', 'docalist-search'),
                    $alias
                ); ?>
            </li>
        </ul><?php
        flush();
    });

    /**
     * remove_old_indices : suppression des anciens index
     */
    add_action('docalist_search_remove_old_indices', function (): void { ?>
        <h2><?= __('Suppression des anciens index', 'docalist-search') ?></h2>
        <ul class="ul-square">
            <li>
                <?= __('Suppression des anciens index qui ne sont plus utilisés.', 'docalist-search') ?>
            </li>
        </ul><?php
        flush();
    });

    /**
     * after_create_index : la création de l'index est terminée
     */
    add_action('docalist_search_after_create_index', function (): void { ?>
        <h2><?= __('Terminé !', 'docalist-search') ?></h2>
        <ul class="ul-square">
            <li>
                <?= __("La création de l'index est terminée.", 'docalist-search') ?>
            </li>
        </ul><?php
        flush();
    });

/**
 * Fin de la page
 */?>
</div>
