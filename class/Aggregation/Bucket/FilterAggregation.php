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
 * Une agrégation de type "bucket" qui regroupe tous les documents qui correspondent à un filtre donné.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations-bucket-filter-aggregation.html
 */
class FilterAggregation extends SingleBucketAggregation
{
    const TYPE = 'filter';

    /**
     * Constructeur
     *
     * @param string    $filter     Définition DSL du filtre à appliquer à l'agrégation.
     * @param array     $parameters Autres paramètres de l'agrégation.
     */
    public function __construct(array $filter, array $parameters = [])
    {
        parent::__construct(['filter' => $filter] + $parameters);
    }
}
