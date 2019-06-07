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

namespace Docalist\Search\Aggregation\Standard;

use Docalist\Search\Aggregation\Bucket\TaxonomyEntriesAggregation;
use Docalist\Search\Indexer\Field\TaxonomyIndexer;

/**
 * Construit une agrégation de type "terms" sur la taxonomie "catégorie".
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class TermsCategory extends TaxonomyEntriesAggregation
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
        !isset($options['title']) && $options['title'] = __('Catégorie', 'docalist-search');
        $options['hierarchy'] = true;
        parent::__construct(TaxonomyIndexer::hierarchyFilter('category'), 'category', $parameters, $options);
    }
}
