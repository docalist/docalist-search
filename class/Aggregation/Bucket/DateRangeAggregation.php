<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2013-2017 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search\Aggregation\Bucket;

use stdClass;

/**
 * Une agrégation de type "range" spécialisée sur les dates.
 *
 * @link
 * https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations-bucket-daterange-aggregation.html
 */
class DateRangeAggregation extends RangeAggregation
{
    const TYPE = 'date_range';

    protected function getBucketFilter(stdClass $bucket)
    {
        $from = isset($bucket->from_as_string) ? $bucket->from_as_string : '*';
        $to = isset($bucket->to_as_string) ? $bucket->to_as_string : '*';

        return $from . '..' . $to;
    }
}
