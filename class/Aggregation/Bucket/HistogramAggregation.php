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
 * Une agrégation de type "buckets" qui regroupe les documents en créant une liste d'intervalles de taille fixe
 * sur un champ numérique donné.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations-bucket-histogram-aggregation.html
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
     * @param array     $renderOptions  Options d'affichage.
     */
    public function __construct($field, $interval, array $parameters = [], array $renderOptions = [])
    {
        $parameters['field'] = $field;
        $parameters['interval'] = $interval;
        parent::__construct($parameters, $renderOptions);
    }
}
