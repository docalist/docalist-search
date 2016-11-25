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
use stdClass;

/**
 * Une agrégation de type "buckets" qui regroupe les documents en fonction des termes trouvés dans un champ donné.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations-bucket-terms-aggregation.html
 */
class TermsAggregation extends MultiBucketsAggregation
{
    const TYPE = 'terms';

    /**
     * Valeur utilisée pour indiquer "non disponible" (Not Available)
     *
     * @var string
     */
    const MISSING = 'n-a';

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

    public function getBucketLabel(stdClass $bucket)
    {
        return ($bucket->key === static::MISSING) ? $this->getMissingLabel() : $bucket->key;
    }

    protected function getMissingLabel() {
        return __('Non disponible', 'docalist-search');
    }
}
