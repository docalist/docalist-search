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

namespace Docalist\Search\Aggregation;

/**
 * Classe de base pour les agrégations de type "metrics" qui retournent une valeur unique.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations-metrics.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
abstract class SingleMetricAggregation extends MetricsAggregation
{
    /**
     * Retourne la valeur calculée par l'agrégation.
     *
     * @return integer|float|null
     */
    public function getValue()
    {
        return $this->getResult('value');
    }

    protected function renderResult()
    {
        return $this->formatValue($this->getValue());
    }
}
