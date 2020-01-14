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

namespace Docalist\Search\Aggregation\Standard;

use Docalist\Search\Aggregation\Bucket\TaxonomyEntriesAggregation;
use Docalist\Search\Indexer\Field\TaxonomyIndexer;

/**
 * Construit une agrégation de type "terms" sur la taxonomie "post_tag" (champ tag).
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class TermsPostTag extends TaxonomyEntriesAggregation
{
    /**
     * Constructeur
     *
     * @param array $parameters     Autres paramètres de l'agrégation.
     * @param array $options        Options d'affichage.
     */
    public function __construct(array $parameters = [], array $options = [])
    {
        !isset($parameters['size']) && $parameters['size'] = 1000;
        !isset($options['title']) && $options['title'] = __('Mots-clés', 'docalist-search');
        parent::__construct(TaxonomyIndexer::codeFilter('post_tag'), 'post_tag', $parameters, $options);
    }
}
