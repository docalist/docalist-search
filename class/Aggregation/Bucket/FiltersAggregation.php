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

/**
 * Une agrégation de type "buckets" qui regroupe les documents qui correspondent aux filtres indiqués.
 *
 * @link
 * https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations-bucket-filters-aggregation.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
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
     * @param array     $options        Options d'affichage.
     */
    public function __construct(array $filters, array $parameters = [], array $options = [])
    {
        $parameters['filters'] = $filters;
        parent::__construct($parameters, $options);
    }
}
