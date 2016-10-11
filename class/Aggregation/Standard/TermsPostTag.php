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
namespace Docalist\Search\Aggregation\Standard;

use Docalist\Search\Aggregation\Bucket\TaxonomyEntriesAggregation;

/**
 * Construit une agrégation de type "terms" sur la taxonomie "post_tag" (champ tag).
 */
class TermsPostTag extends TaxonomyEntriesAggregation
{
    public function __construct()
    {
        parent::__construct('tag', 'post_tag', ['size' => 1000/*, 'missing' => self::MISSING*/]);
        $this->setTitle('Mots-clés');
    }
}
