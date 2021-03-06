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

namespace Docalist\Search\Aggregation\Bucket;

use Docalist\Search\Aggregation\MultiBucketsAggregation;
use stdClass;

/**
 * Une agrégation de type "buckets" qui regroupe les documents dans une liste d'intervalles donnés.
 *
 * @link
 * https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations-bucket-range-aggregation.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class RangeAggregation extends MultiBucketsAggregation
{
    const TYPE = 'range';

    /**
     * Constructeur
     *
     * @param string    $field          Champ sur lequel porte l'agrégation.
     * @param array     $ranges         Un tableau indiquant les intervalles à générer. Chaque intervalle
     *                                  est lui-même un tableau qui peut contenir les clés 'key', 'from' et 'to' :
     *                                  [
     *                                      [ 'key' => 'moins de 50', 'to'   =>  50              ],
     *                                      [ 'key' => 'de 50 à 100', 'from' =>  50, 'to' => 100 ],
     *                                      [ 'key' => '100 et plus', 'from' => 100              ]
     *                                  ]
     * @param array     $parameters     Autres paramètres de l'agrégation.
     * @param array     $options        Options d'affichage.
     */
    public function __construct(string $field, array $ranges, array $parameters = [], array $options = [])
    {
        $parameters['field'] = $field;
        $parameters['ranges'] = $ranges;
        parent::__construct($parameters, $options);
    }

    /**
     * {@inheritDoc}
     */
    final protected function prepareBucket(stdClass $bucket): ?stdClass
    {
        /**
         * Pour les agrégations de type range, ES retourne des buckets pour tous les ranges, même
         * si le doc_count obtenu est à zéro.
         * Pour ne pas afficher ces buckets à zéro, on surcharge prepareBucket() et on ne retourne
         * un bucket que si on a un count.
         */
        return $bucket->doc_count === 0 ? null : parent::prepareBucket($bucket);
    }

    /**
     * {@inheritDoc}
     */
    protected function getBucketFilter(stdClass $bucket): string // pas final, surchargée dans DateRange
    {
        $from = isset($bucket->from) ? $bucket->from : '*';
        $to = isset($bucket->to) ? $bucket->to : '*';

        return $from . '..' . $to;
    }

    /**
     * {@inheritDoc}
     */
    final protected function getBucketClass(stdClass $bucket): string
    {
        // On génère : Moins de 500 -> 'r-500', De 500 à 1000 -> 'r500-1000', Plus de 1000 -> 'r1000-'
        $from = isset($bucket->from) ? $bucket->from : '';
        $to = isset($bucket->to) ? $bucket->to : '';

        return sprintf('r%s-%s', $from, $to);
    }
}
