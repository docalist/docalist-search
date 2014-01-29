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
 * Cette vue est affichée lorsque une réindexation manuelle est lancée.
 *
 * Lorsqu'elle est exécutée, cette vue n'affiche rien : elle se contente
 * d'installer des filtres qui seront appellés au bon moment par le réindexeur
 * au fur et à mesure de la réindexation.
 *
 * Six filtres sont installés :
 * - docalist_search_before_reindex : affiche le début de la page (début de la
 *   div.wrap, titre h2, p.description).
 *
 * - docalist_search_before_reindex_type : début de la réindexation d'un type
 *   donné. Affiche un titre h3 avec le nom du type en cours et ouvre un <ul>.
 *
 * - docalist_search_before_flush : juste avant que le buffer ne soit envoyé
 *   à elastic search. Affiche le début d'un <li> indiquant le nombre de
 *   documents analysés, la taille du buffer et stocke l'heure de début du
 *   flush.
 *
 * - docalist_search_after_flush : fin du flush, affiche le temps pris par le
 *   flush et ferme le <li> ouvert dans before_flush.
 *
 * - docalist_search_after_reindex_type : fin de la réindexation du type en
 *   cours. Ferme le <ul> ouvert par docalist_search_before_reindex_type et
 *   indique le nombre de documents indexés.
 *
 * - docalist_search_after_reindex : la réindexation est terminée. Affiche une
 *   synthèse générale avec les stats fournies par l'indexeur.
 *
 * @param array $types Un tableau contenant la liste des types qui vont être
 * réindexés.
 */
?>

<?php
    // Nombre de documents indexés pour un type donné.
    // Initialisé à zéro dans before_reindex_type, incrémenté et utilisé
    // dans before_flush.
    $total;

    // Timestamp contenant l'heure de début d'un flush
    // Initialisé par before_flush, utilisé dans after_flush
    $flushTime;
?>

<?php
/**
 * before_reindex : début de la réindexation.
 *
 * Affiche le début de la page (début de la div.wrap, titre h2, p.description).
 */
add_action('docalist_search_before_reindex', function(array $types) { ?>
    <div class="wrap">
        <?= screen_icon() ?>
        <h2><?= __("Réindexation manuelle", 'docalist-search') ?></h2>

        <p class="description"><?php
            //@formatter:off
            printf(
                __(
                    'Vous avez lancé la réindexation des documents de type
                    <strong>%s</strong>.<br />
                    La page affichera des informations supplémentaires au fur
                    et à mesure de l\'avancement. Veuillez patienter.',
                    'docalist-search'
                ),
                implode(', ', $types)
            );
            // @formatter:on
            ?>
        </p>

        <h3><?= __('Création / mise à jour des paramètres de l\'index', 'docalist-search') ?></h3>
        <?php
        flush();
}, 1, 1); ?>

<?php
/**
 * before_reindex_type : début de la réindexation d'un type donné.
 *
 * Affiche un titre h3 et ouvre un <ul>.
 */
add_action('docalist_search_before_reindex_type', function($type, $label) use(&$total) { ?>
    <p><?= __('Index OK.', 'docalist-search') ?></p>
    <h3><?= $label ?></h3>
    <p><?= __('Chargement des documents à indexer à partir de la base WordPress...', 'docalist-search') ?></p>
    <ul class="ul-square">
    <?php
    $total = 0;
    flush();
}, 1, 2);
?>

<?php
/**
 * before_flush : juste avant que le buffer ne soit envoyé à elastic search.
 *
 * Affiche le début d'un <li> indiquant le nombre de documents analysés, la
 * taille du buffer et stocke l'heure de début du flush.
 */
add_action('docalist_search_before_flush', function($count, $size) use(& $total, & $flushTime){
    $total += $count;
    $flushTime = microtime(true);
    $msg =__('<li>%d documents indexés, flush du cache (%s)... ', 'docalist-search');
    printf($msg, $total, size_format($size));
    flush();
}, 1, 2);
?>

<?php
/**
 * after_flush : fin du flush.
 *
 * Affiche le temps pris par le flush et ferme le <li>.
 */
add_action('docalist_search_after_flush', function($count, $size) use(& $flushTime){
    $msg =__('OK (%s secondes)</li>', 'docalist-search');
    printf($msg, round(microtime(true) - $flushTime, 2));
    flush();
}, 1, 2);
?>

<?php
/**
 * after_reindex_type : fin de la réindexation du type en cours.
 *
 * Ferme le <ul> ouvert par docalist_search_before_reindex_type et indique le
 * nombre de documents indexés.
 */
add_action('docalist_search_after_reindex_type', function($type, $label, $stats) { ?>
    </ul>
    <?php
    $msg =__('Terminé. Nouveaux documents : %d, mis à jour : %d, supprimés : %d.', 'docalist-search');
    $msg = sprintf($msg, $stats['added'], $stats['updated'], $stats['removed']);
    printf('<p>%s</p>', $msg);
    flush();
}, 1, 3);
?>

<?php
/* Fin de la réindexation */
/**
 *  after_reindex : la réindexation est terminée.
 *
 *  Affiche une synthèse générale avec les stats fournies par l'indexeur.
 */
add_action('docalist_search_after_reindex', function(array $types, array $stats) { ?>
        <h3><?= __('La réindexation est terminée', 'docalist-search') ?></h3>

        <?php
            $items = [
                'start'     => __('Heure de début'),
                'end'       => __('Heure de fin'),
                'time'      => __('Durée (secondes)'),

                'added'     => __('Documents ajoutés', 'docalist-search'),
                'updated'   => __('Documents mis à jour', 'docalist-search'),
                'removed'   => __('Documents supprimés', 'docalist-search'),

                'avgsize'   => __('Taille moyenne des documents (octets)'),
                'minsize'   => __('Plus petit document (octets)'),
                'maxsize'   => __('Plus grand document (octets)'),

            ];

            $formatTime = function($timestamp, $format = 'H:i:s') { // pas trouvé de fonction wp qui fasse ça...
                return gmdate($format, $timestamp + get_option('gmt_offset') * HOUR_IN_SECONDS);
            };

            foreach($types as $type => $label){
                $stats[$type]['start'] = $formatTime($stats[$type]['start']);
                $stats[$type]['end'] = $formatTime($stats[$type]['end']);
            }
        ?>

        <table class="widefat fixed">

        <thead>
            <tr>
                <th>Types de document</th>
                <?php foreach($types as $type):?>
                    <th><?= $type ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>

        <?php foreach($items as $item => $label): ?>
            <tr>
                <th><?= $label ?></th>
                <?php foreach($types as $type => $label):?>
                    <td><?= $stats[$type][$item] ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>

        </table>
    </div>
    <?php flush();
}, 10, 2);
?>