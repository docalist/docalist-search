<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Search\Aggregation\Metrics;

use Docalist\Search\Aggregation\SingleMetricAggregation;

/**
 * Une agrégation qui retourne la somme d'un champ (numérique).
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations-metrics-sum-aggregation.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class SumAggregation extends SingleMetricAggregation
{
    const TYPE = 'sum';
}
