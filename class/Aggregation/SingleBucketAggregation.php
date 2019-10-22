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

namespace Docalist\Search\Aggregation;

/**
 * Classe de base pour les agrégations de type "bucket" qui retournent un bucket unique.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations-bucket.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
abstract class SingleBucketAggregation extends BucketAggregation
{
    /**
     * Retourne le nombre de documents obtenus pour cette agrégation.
     *
     * @return int
     */
    public function getDocCount(): int
    {
        return $this->getResult('doc_count');
    }
}
