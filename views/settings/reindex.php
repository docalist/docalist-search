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
 */
namespace Docalist\Search\Views;

use Docalist\Search\SettingsPage;

/**
 * Cette vue est affichée lors de la création de l'index ou lorsqu'une indexation manuelle est lancée.
 *
 * Lorsqu'elle est exécutée, cette vue se contente d'installer des filtres qui seront appellés au bon moment par le
 * réindexeur au fur et à mesure de l'indexation.
 *
 * Six filtres sont installés :
 * - docalist_search_before_reindex : affiche le début de la page.
 *
 * - docalist_search_before_reindex_type : début de la réindexation d'un type donné.
 *   Affiche un titre h3 avec le nom du type en cours et ouvre un <ul>.
 *
 * - docalist_search_before_flush : juste avant que le buffer ne soit envoyé à elastic search.
 *   Affiche le début d'un <li> indiquant le nombre de documents analysés, la taille du buffer et stocke l'heure
 *   de début du flush.
 *
 * - docalist_search_after_flush : fin du flush, affiche le temps pris par le flush et ferme le <li> ouvert.
 *
 * - docalist_search_after_reindex_type : fin de la réindexation du type en cours. Ferme le <ul> ouvert par
 *   docalist_search_before_reindex_type et indique le nombre de documents indexés.
 *
 * - docalist_search_after_reindex : l'indexation de tous les types de contenus est terminée.
 *   Affiche une synthèse générale avec les stats fournies par l'indexeur.
 *
 * @var SettingsPage    $this
 */

// Initialise les variables dont on a besoin

// Nombre de documents indexés pour un type donné. Réinitialisé dans before_reindex_type, incrémenté dans before_flush.
$total = 0;

// Timestamp contenant l'heure de début d'un flush. Initialisé par before_flush, utilisé dans after_flush
$flushTime = 0;
?>

<div class="wrap">
    <h1><?= __("Création de l'index ElasticSearch", 'docalist-search') ?></h1>

    <p class="description"><?=
        __(
            "L'index ElasticSearch va être créé et les types de contenus que vous avez choisi vont être indexés.
             La page affichera des informations supplémentaires au fur et à mesure, veuillez patienter.",
            'docalist-search'
        ) ?>
    </p>
<?php

/**
 * create_index : création de l'index ElasticSearch.
 */
add_action('docalist_search_before_create_index', function($index, array $settings) { ?>
    <h2>
    <?= sprintf(
            __("Création de l'index '%s' dans le cluster ElasticSearch", 'docalist-search'),
            $index
        )
    ?>
    </h2>
    <ul class="ul-square">
        <li>
            <?php
                $shards = $settings['settings']['index']['number_of_shards'];
                $shards = sprintf(
                    _n('un shard', '%d shards', $shards, 'docalist-search'),
                    $shards
                );

                $replicas = $settings['settings']['index']['number_of_replicas'];
                $replicas = sprintf(
                    _n('un réplicat', '%s réplicats', $replicas, 'docalist-search'),
                    $replicas ?: __('aucun', 'docalist-search')
                );

                $types = count($settings['mappings']);
                $types = sprintf(
                    _n('un type de contenu', '%d types de contenu', $types, 'docalist-search'),
                    $types
                );

                printf(
                    __('Votre index contiendra %s, %s et %s.', 'docalist-search'),
                    $shards, $replicas, $types
                );
            ?>
        </li>
    </ul>
    <?php
    flush();
}, 10, 2);

/**
 * activate_index : création / maj des alias.
 */
add_action('docalist_search_activate_index', function($alias, $index) { ?>
    <h2><?= __('Activation du nouvel index', 'docalist-search') ?></h2>
    <ul class="ul-square">
        <li>
            <?php
                printf(
                    __("L'alias <code>%s</code> va maintenant utiliser l'index <code>%s</code>.", 'docalist-search'),
                    $alias, $index
                );
            ?>
        </li>
    </ul>
    <?php
    flush();
}, 10, 2);

/**
 * remove_old_indices : suppression des anciens index
 */
add_action('docalist_search_remove_old_indices', function() { ?>
    <h2><?= __('Suppression des anciens index', 'docalist-search') ?></h2>
    <ul class="ul-square">
        <li>
            <?= __("Suppression des index existants qui ne sont plus utiles.", 'docalist-search') ?>
        </li>
    </ul>
    <?php
    flush();
});

/**
 * remove_old_indices : suppression des anciens index
 */
add_action('docalist_search_after_create_index', function() { ?>
    <h2><?= __('Terminé !', 'docalist-search') ?></h2>
    <ul class="ul-square">
        <li>
            <?= __("La création de l'index est terminée.", 'docalist-search') ?>
        </li>
    </ul>
    <?php
    flush();
});

/**
 * before_reindex : début de la réindexation.
 *
 * Affiche le début de la page (début de la div.wrap, titre h2, p.description).
 */
/*
add_action('docalist_search_before_reindex', function(array $types) { ?>
    <h2><?= __('Création / mise à jour des paramètres de l\'index', 'docalist-search') ?></h2>
    <?php
    flush();
});
*/

/**
 * before_reindex_type : début de la réindexation d'un type donné.
 *
 * Affiche un titre h3 et ouvre un <ul>.
 */
add_action('docalist_search_before_reindex_type', function($type, $label) use(& $total) { ?>
    <h2><?= $label ?></h2>
    <p><?= __('Chargement de la liste des documents à indexer...', 'docalist-search') ?></p>
    <ul class="ul-square">
    <?php
    $total = 0;
    flush();
}, 10, 2);

/**
 * before_flush : juste avant que le buffer ne soit envoyé à elastic search.
 *
 * Affiche le début d'un <li> indiquant le nombre de documents analysés et la taille du buffer et stocke l'heure
 * de début du flush.
 */
add_action('docalist_search_before_flush', function($count, $size) use(& $total, & $flushTime){
    $total += $count;
    $flushTime = microtime(true);
    echo '<li>';
    $msg =__('%d documents indexés, flush du cache (%s)... ', 'docalist-search');
    printf($msg, $total, size_format($size));
    flush();
}, 10, 2);

/**
 * after_flush : fin du flush.
 *
 * Affiche le temps pris par le flush et ferme le <li>.
 */
add_action('docalist_search_after_flush', function($count, $size) use(& $flushTime){
    $msg =__('OK (%s secondes)', 'docalist-search');
    printf($msg, round(microtime(true) - $flushTime, 3));
    echo '</li>';
    flush();
}, 10, 2);

/**
 * after_reindex_type : fin de la réindexation du type en cours.
 *
 * Ferme le <ul> ouvert par docalist_search_before_reindex_type et indique le
 * nombre de documents indexés.
 */
add_action('docalist_search_after_reindex_type', function($type, $label, $stats) { ?>
    </ul>
    <?php
    $msg =__('Terminé, %d documents indexés au total.', 'docalist-search');
    $msg = sprintf($msg, $stats['added']);
    printf('<p>%s</p>', $msg);
    flush();
}, 10, 3);

/**
 *  after_reindex : la réindexation est terminée.
 *
 *  Affiche une synthèse générale avec les stats fournies par l'indexeur.
 */
add_action('docalist_search_after_reindex', function(array $types, array $stats) { ?>
    <h2><?= __("Statistiques sur les contenus indexés", 'docalist-search') ?></h2>

    <?php
        $items = [
            'added'     => __('Nombre de documents indexés', 'docalist-search'),

            'avgsize'   => __('Taille moyenne des documents'),
            'minsize'   => __('Plus petit document'),
            'maxsize'   => __('Plus grand document'),

            'time'      => __("Durée de l'indexation (secondes)"),
        ];

        $formatTime = function($timestamp, $format = 'H:i:s') { // pas trouvé de fonction wp qui fasse ça...
            return gmdate($format, $timestamp + get_option('gmt_offset') * HOUR_IN_SECONDS);
        };

        foreach($stats as $type => & $typeStats) {
            $typeStats['start'] = $formatTime($typeStats['start']);
            $typeStats['end'] = $formatTime($typeStats['end']);
            $typeStats['avgsize'] = size_format($typeStats['avgsize']);
            $typeStats['minsize'] = size_format($typeStats['minsize']);
            $typeStats['maxsize'] = size_format($typeStats['maxsize']);
        }
        unset($typeStats);
    ?>

    <table class="widefat fixed">

    <thead>
        <tr>
            <td class="row-title"><?=__('Statistiques', 'docalist-search')?></td>
            <?php foreach(array_keys($stats) as $type):?>
                <th><?= isset($types[$type]) ? $types[$type] : $type ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>

    <?php $row = 0; foreach($items as $item => $label): ?>
        <tr class="<?=$row % 2 ? '' : 'alternate'?>">
            <td class="row-title"><?= $label ?></td>
            <?php foreach($stats as $typeStats):?>
                <td><?= $typeStats[$item] ?></td>
            <?php endforeach; ?>
        </tr>
    <?php ++$row; endforeach; ?>

    </table>
    <?php flush();
}, 10, 2);
?>
</div>
