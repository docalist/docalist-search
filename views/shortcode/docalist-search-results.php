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

namespace Docalist\Search\Views\Shortcode;

/**
 * Template "(title)" pour le shortcode "docalist_search_results".
 *
 * Les variables passées à ce template sont les suivantes :
 *
 * @var string      $url            L'url de recherche indiquée dans le shortcode.
 * @var string[]    $attributes     Les attributs du shortcode.
 * @var string      $template       Le path du template utilisé (i.e. ce fichier).
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */

// Détermine la fonction à utiliser pour afficher le contenu des posts
switch ($attributes['template']) {
    case '(excerpt)':
        $contentFunction = 'the_excerpt';
        break;

    case '(content)':
        $contentFunction = 'the_content';
        break;

    default: // '(title)' ou template non reconnu
        $contentFunction = '';
} ?>

<div class="docalist-search-results"><?php
    if (have_posts()) { ?>
        <ul class="hfeed"><?php
            while (have_posts()) {
                the_post(); ?>
                <li <?php post_class() ?>>
                    <h3 class="entry-title">
                        <a rel="bookmark" href="<?= esc_url(get_permalink()) ?>"><?php the_title() ?></a>
                    </h3><?php
                    if ($contentFunction !== '') { ?>
                        <div class="entry-content">
                            <?php $contentFunction() ?>
                        </div><?php
                    } ?>
                </li><?php
            } ?>
        </ul><?php
        if ($attributes['more'] !== 'false' && $attributes['more'] !== '0') { ?>
            <form class="more" action="<?= esc_url($url) ?>" method="post">
                <input type="submit" value="<?= esc_attr($attributes['more']) ?>" />
            </form><?php
        }
    } else { ?>
        <ul class="no-results">
            <li><a href="<?= esc_url($url) ?>"><?= $attributes['no-results'] ?></a></li>
        </ul><?php
    } ?>
</div>
