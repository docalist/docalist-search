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
namespace Docalist\Search\Aggregation\Bucket;

use Docalist\Search\Aggregation\SingleBucketAggregation;

/**
 * Une agrégation de type "bucket" qui regroupe tous les documents sans tenir compte de la recherche en cours.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations-bucket-global-aggregation.html
 */
class GlobalAggregation extends SingleBucketAggregation
{
    const TYPE = 'global';
}
