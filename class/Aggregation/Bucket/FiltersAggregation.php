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
 * Une agrégation de type "buckets" qui regroupe les documents qui correspondent aux filtres indiqués.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations-bucket-filters-aggregation.html
 */
class FiltersAggregation extends MultiBucketsAggregation
{
    const TYPE = 'filters';

    /**
     * Constructeur
     *
     * @param array     $filters        Une liste de filtres à appliquer à l'agrégation.
     *                                  Les clés du tableau indiquent le nom des filtres et les valeurs associées
     *                                  contiennent la définition DSL du filtre.
     * @param array     $parameters     Autres paramètres de l'agrégation.
     * @param array     $renderOptions  Options d'affichage.
     */
    public function __construct(array $filters, array $parameters = [], array $renderOptions = [])
    {
        $parameters['filters'] = $filters;
        parent::__construct($parameters, $renderOptions);
    }
}
