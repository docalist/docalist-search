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

use Docalist\Search\Aggregation\Bucket\RangeAggregation;

/**
 * Vue par défaut pour les agrégations "range".
 *
 * @var RangeAggregation $this L'agrégation à afficher.
 */
if ($buckets = $this->getBuckets()) {
    // ES génère les buckets même si le doc_count obtenu est à zéro.
    // Comme on ne veut que les buckets pas "vides", il faut qu'on teste.
    // Potentiellement, tous les buckets peuvent être vides, et dans ce cas, on ne veut pas générer le titre et le ul.
    // Du coup, on génère les buckets dans une chaine et on n'affiche la facette que si on a quelque chose.
    $items = '';
    foreach ($buckets as $bucket) {
        $count = $bucket->doc_count;
        if ($count === 0) {
            continue;
        }
        $label = $this->getBucketLabel($bucket);
        $class = sprintf(
            'range-%s-%s',
            isset($bucket->from) ? $bucket->from : 'less-than',
            isset($bucket->to) ? $bucket->to : 'and-more'
        );
        $url = 'javascript:alert("Pas encore implémenté");';

        $items .= sprintf(
            '<li class="%s"><a href="%s"><span>%s</span> <em>%d</em></a></li>',
            esc_attr($class), esc_attr($url), $label, $count
        );
    }

    if ($items) {
        printf('<h3>%s</h3>', $this->getTitle() ?: $this->getName());
        printf('<ul class="%s">%s</ul>', $this->getType(), $items);
    }
}
