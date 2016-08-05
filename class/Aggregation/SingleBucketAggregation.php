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
namespace Docalist\Search\Aggregation;

/**
 * Classe de base pour les agrégations de type "bucket" qui retournent un bucket unique.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations-bucket.html
 */
abstract class SingleBucketAggregation extends BucketAggregation
{
    /**
     * Retourne le nombre de documents obtenus pour cette agrégation.
     *
     * @return int
     */
    public function getDocCount()
    {
        return $this->getResult('doc_count');
    }
}
