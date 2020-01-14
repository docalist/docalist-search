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

namespace Docalist\Search\Aggregation\Metrics;

use Docalist\Search\Aggregation\MultiMetricsAggregation;

/**
 * Une agrégation qui retourne des statistiques (min, max, sum, count et avg) sur un champ (numérique).
 *
 * @link
 * https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations-metrics-stats-aggregation.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class StatsAggregation extends MultiMetricsAggregation
{
    const TYPE = 'stats';

    /**
     * Retourne la valeur minimale calculée par l'agrégation.
     *
     * @return float
     */
    final public function getMin(): float
    {
        return $this->getResult('min') ?? 0.0;
    }

    /**
     * Retourne la valeur maximale calculée par l'agrégation.
     *
     * @return float
     */
    final public function getMax(): float
    {
        return $this->getResult('max') ?? 0.0;
    }

    /**
     * Retourne la somme calculée par l'agrégation.
     *
     * @return float
     */
    final public function getSum(): float
    {
        return $this->getResult('sum') ?? 0.0;
    }

    /**
     * Retourne le nombre de valeurs trouvées par l'agrégation.
     *
     * @return integer
     */
    final public function getCount(): int
    {
        return $this->getResult('count') ?? 0;
    }

    /**
     * Retourne la moyenne calculée par l'agrégation.
     *
     * @return float
     */
    final public function getAvg(): float
    {
        return $this->getResult('avg') ?? 0.0;
    }

    /**
     * {@inheritDoc}
     */
    final public function getDefaultOptions(): array
    {
        $options = parent::getDefaultOptions();
        $options['container.tooltip'] = __('{count} fiche(s), min {min}, max {max}, moyenne {avg}', 'docalist-search');

        return $options;
    }

    /**
     * {@inheritDoc}
     */
    final protected function renderResult(): string
    {
        // On retourne la somme comme résultat
        return $this->formatValue($this->getSum());
    }

    /**
     * {@inheritDoc}
     */
    final protected function getContainerAttributes(): array
    {
        $attributes = parent::getContainerAttributes();

        if (isset($attributes['title'])) {
            $attributes['title'] = strtr($attributes['title'], [
                '{count}'   => number_format($this->getCount()),
                '{min}'     => $this->formatValue($this->getMin()),
                '{max}'     => $this->formatValue($this->getMax()),
                '{avg}'     => $this->formatValue($this->getAvg()),
            ]);
        }

        return $attributes;
    }
}
