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

use Docalist\Search\Aggregation\MultiBucketsAggregation;

/**
 * Une agrégation de type "buckets" qui regroupe les documents dans une liste d'intervalles donnés.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations-bucket-range-aggregation.html
 */
class RangeAggregation extends MultiBucketsAggregation
{
    const TYPE = 'range';

    /**
     * Constructeur
     *
     * @param string $field Champ sur lequel porte l'agrégation.
     * @param array  $ranges Un tableau indiquant la liste des intervalles à générer. Chaque intervalle est lui-même
     * un tableau contenant les clés 'from' et/ou 'to'. Exemple :
     * [
     *     ['to' => 50],
     *     ['from' => 50, 'to' => 100],
     *     ['from' => 100]
     * ]
     */
    public function __construct($field, array $ranges)
    {
        parent::__construct(['field' => $field, 'ranges' => $ranges]);
    }
}
