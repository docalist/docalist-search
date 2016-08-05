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
 * Classe de base pour les agrégations de type "metrics" qui retournent une valeur unique.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations-metrics.html
 */
abstract class SingleMetricAggregation extends MetricsAggregation
{
    /**
     * Retourne la valeur calculée par l'aggrégation.
     *
     * @return integer|float|null
     */
    public function getValue()
    {
        return $this->getResult('value');
    }
}
