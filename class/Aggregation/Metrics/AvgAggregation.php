<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2013-2016 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search\Aggregation\Metrics;

use Docalist\Search\Aggregation\SingleMetricAggregation;

/**
 * Une agrégation qui retourne la moyenne d'un champ (numérique).
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations-metrics-avg-aggregation.html
 */
class AvgAggregation extends SingleMetricAggregation
{
    const TYPE = 'avg';
}