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

use Docalist\Search\Aggregation\Metrics\StatsAggregation;

/**
 * Vue par défaut pour les agrégations "stats".
 *
 * @var StatsAggregation $this L'agrégation à afficher.
 */
if ($count = $this->getCount()) {
    $details = sprintf('%s fiche(s), min %s, max %s, moyenne %s',
        (string) $count,
        $this->formatValue($this->getMin()),
        $this->formatValue($this->getMax()),
        $this->formatValue($this->getAvg())
    );

    printf(
        '<span title="%s">%s</span> <em>%s</em>',
        esc_attr($details),
        $this->formatValue($this->getSum()),
        $this->getTitle() ?: $this->getName()
    );
}
