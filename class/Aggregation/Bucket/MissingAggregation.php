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

use Docalist\Search\Aggregation\SingleBucketAggregation;

/**
 * Une agrégation de type "bucket" qui regroupe tous les documents qui ne contiennent pas un champ donné.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations-bucket-missing-aggregation.html
 */
class MissingAggregation extends SingleBucketAggregation
{
    const TYPE = 'missing';

    /**
     * Constructeur
     *
     * @param string    $field          Champ sur lequel porte l'agrégation.
     * @param array     $parameters     Autres paramètres de l'agrégation.
     * @param array     $renderOptions  Options d'affichage.
     */
    public function __construct($field, array $parameters = [], array $renderOptions = [])
    {
        $parameters['field'] = $field;
        parent::__construct($parameters, $renderOptions);
    }
}
