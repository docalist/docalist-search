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

use Docalist\Search\Aggregation\MultiMetricsAggregation;

/**
 * Une agrégation qui retourne des statistiques (min, max, sum, count et avg) sur un champ (numérique).
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations-metrics-stats-aggregation.html
 */
class StatsAggregation extends MultiMetricsAggregation
{
    const TYPE = 'stats';
    const DEFAULT_VIEW = 'docalist-search:aggregations/metrics/stats';

    /**
     * Retourne la valeur minimale calculée par l'aggrégation.
     *
     * @return integer|float|null
     */
    public function getMin()
    {
        return $this->getResult('min');
    }

    /**
     * Retourne la valeur maximale calculée par l'aggrégation.
     *
     * @return integer|float|null
     */
    public function getMax()
    {
        return $this->getResult('max');
    }

    /**
     * Retourne la somme calculée par l'aggrégation.
     *
     * @return integer|float|null
     */
    public function getSum()
    {
        return $this->getResult('sum');
    }

    /**
     * Retourne le nombre de valeurs trouvées par l'aggrégation.
     *
     * @return integer|null
     */
    public function getCount()
    {
        return $this->getResult('count');
    }

    /**
     * Retourne la moyenne calculée par l'aggrégation.
     *
     * @return float|null
     */
    public function getAvg()
    {
        return $this->getResult('avg');
    }
}
