<?php
/**
 * This file is part of the 'SVB Plugin' package.
 *
 * Copyright (C) 2015-2016 Artwaï, Docalist
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search\Aggregation\Standard;

use Docalist\Search\Aggregation\Bucket\TaxonomyEntriesAggregation;

/**
 * Construit une agrégation de type "terms" sur la taxonomie "catégorie".
 */
class TermsCategory extends TaxonomyEntriesAggregation
{
    public function __construct()
    {
        parent::__construct('category', 'category', ['size' => 1000/*, 'missing' => self::MISSING*/]);
        $this->setTitle('Catégorie');
    }
}
