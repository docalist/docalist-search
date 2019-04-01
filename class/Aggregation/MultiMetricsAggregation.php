<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Search\Aggregation;

/**
 * Classe de base pour les agrégations de type "metrics" qui retournent plusieurs valeurs.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations-metrics.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
abstract class MultiMetricsAggregation extends MetricsAggregation
{
}
