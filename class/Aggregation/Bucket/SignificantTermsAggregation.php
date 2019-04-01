<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Search\Aggregation\Bucket;

use Docalist\Search\Aggregation\MultiBucketsAggregation;

/**
 * Une agrégation de type "buckets" qui regroupe les documents en fonction des termes intéressants (ou inusuels)
 * trouvés dans un champ donné.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/
 * search-aggregations-bucket-significantterms-aggregation.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class SignificantTermsAggregation extends MultiBucketsAggregation
{
    const TYPE = 'significant_terms';

    /**
     * Constructeur
     *
     * @param string    $field          Champ sur lequel porte l'agrégation.
     * @param array     $parameters     Autres paramètres de l'agrégation.
     * @param array     $options        Options d'affichage.
     */
    public function __construct($field, array $parameters = [], array $options = [])
    {
        $parameters['field'] = $field;
        parent::__construct($parameters, $options);
    }
}
