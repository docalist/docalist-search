<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2020 Daniel Ménard
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
     * @return float
     */
    final public function getValue(): float
    {
        return $this->getResult('value') ?? 0.0;
    }

    /**
     * {@inheritDoc}
     */
    final protected function renderResult(): string
    {
        return $this->formatValue($this->getValue());
    }
}
