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

namespace Docalist\Search\Aggregation\Bucket;

use Docalist\Search\Aggregation\MultiBucketsAggregation;

/**
 * Une agrégation de type "buckets" qui regroupe les documents en créant une liste d'intervalles de taille fixe
 * sur un champ numérique donné.
 *
 * @link
 * https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations-bucket-histogram-aggregation.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class HistogramAggregation extends MultiBucketsAggregation
{
    const TYPE = 'histogram';

    /**
     * Constructeur
     *
     * @param string    $field          Champ sur lequel porte l'agrégation.
     * @param array     $interval       Taille de chacune des barres de l'histogramme généré.
     * @param array     $parameters     Autres paramètres de l'agrégation.
     * @param array     $options        Options d'affichage.
     */
    public function __construct($field, $interval, array $parameters = [], array $options = [])
    {
        $parameters['field'] = $field;
        $parameters['interval'] = $interval;
        parent::__construct($parameters, $options);
    }
}
