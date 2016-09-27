<?php
/**
 * This file is part of the 'Docalist Search' plugin.
 *
 * Copyright (C) 2016-2016 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search\Views\Aggregation\Metrics;

use Docalist\Search\Aggregation\Bucket\TermsAggregation;

/**
 * Vue par défaut pour les agrégations "terms".
 *
 * @var TermsAggregation $this L'agrégation à afficher.
 */
if ($buckets = $this->getBuckets()) {
    $field = $this->getParameter('field');
    $searchUrl = $this->getSearchRequest()->getSearchUrl();

    // Titre de la facette
    printf('<h3%s>%s</h3>',
        $searchUrl->hasFilter($field) ? ' class="filter-active"' : '',
        $this->getTitle() ?: $this->getName()
    );

    // Liste des termes
    printf('<ul class="%s">', $this->getType());
    foreach ($buckets as $bucket) {
        $count = $bucket->doc_count;
        $term = $bucket->key;
        $label = $this->getBucketLabel($bucket);
        $class = $term;
        $searchUrl->hasFilter($field, $term) && $class .= ' filter-active';
        $url = $searchUrl->toggleFilter($field, $term);

        printf(
            '<li class="%s"><a href="%s"><span>%s</span> <em>%d</em></a></li>',
            esc_attr($class), esc_attr($url), $label, $count
        );
    }
    echo '</ul>';
}
