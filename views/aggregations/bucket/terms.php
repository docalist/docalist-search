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
 * @var TermsAggregation    $this   L'agrégation à afficher.
 * @var string              $title  Optionnel, le titre de l'agrégation.
 */
if ($buckets = $this->getBuckets()) {
    printf('<h3>%s</h3>', isset($title) ? $title : $this->getName());
    printf('<ul class="%s">', $this->getType());
    foreach ($buckets as $bucket) {
        $count = $bucket->doc_count;
        $label = $this->getBucketLabel($bucket);
        $class = $bucket->key;
        $url = '#';

        printf(
            '<li class="%s"><a href="%s"><strong>%s</strong> <em>%d</em></a></li>',
            esc_attr($class), esc_attr($url), $label, $count
        );
    }
    echo '</ul>';
}
