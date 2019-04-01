<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Search\Aggregation\Bucket;

use stdClass;

/**
 * Une agrégation de type "range" spécialisée sur les dates.
 *
 * @link
 * https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations-bucket-daterange-aggregation.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
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
