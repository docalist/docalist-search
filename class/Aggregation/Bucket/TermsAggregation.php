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
 * Une agrégation de type "buckets" qui regroupe les documents en fonction des termes trouvés dans un champ donné.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations-bucket-terms-aggregation.html
 */
class TermsAggregation extends MultiBucketsAggregation
{
    const TYPE = 'terms';

    /**
     * Constructeur
     *
     * @param string $field champ sur lequel porte l'agrégation.
     */
    public function __construct($field)
    {
        parent::__construct(['field' => $field]);
    }
}
