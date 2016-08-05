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
 * Classe de base pour les agrégations de type "metrics".
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations-metrics.html
 */
abstract class MetricsAggregation extends BaseAggregation
{
    /**
     * Constructeur
     *
     * @param string $field champ sur lequel porte l'agrégation.
     */
    public function __construct($field = null)
    {
        parent::__construct($field ? ['field' => $field] : []);
    }
}
