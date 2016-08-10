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
namespace Docalist\Search\Aggregation;

/**
 * Classe de base pour les agrégations de type "bucket".
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations-bucket.html
 */
abstract class BucketAggregation extends BaseAggregation
{
    /**
     * Retourne la liste des buckets générés par l'agrégation.
     *
     * @return array
     */
    public function getBuckets()
    {
        return $this->getResult('buckets') ?: [];
    }

    /**
     * Retourne le libellé à afficher pour le bucket passé en paramètre.
     *
     * @param object $bucket Les données du bucket : un objet avec des champs comme 'key', 'doc_count', 'from', etc.
     *
     * @return string Le libellé à afficher pour ce bucket.
     */
    public function getBucketLabel($bucket)
    {
        return $bucket->key;
    }
}
