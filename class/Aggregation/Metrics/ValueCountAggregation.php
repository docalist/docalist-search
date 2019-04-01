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
 * Une agrégation qui retourne le nombre total de valeurs trouvées dans un champ.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations-metrics-
 * valuecount-aggregation.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ValueCountAggregation extends SingleMetricAggregation
{
    const TYPE = 'value_count';
}
