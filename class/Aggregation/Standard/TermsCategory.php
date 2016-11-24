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
 * Construit une agrégation de type "terms" sur la taxonomie "catégorie".
 */
class TermsCategory extends TaxonomyEntriesAggregation
{
    /**
     * Constructeur
     *
     * @param array $parameters     Autres paramètres de l'agrégation.
     * @param array $renderOptions  Options d'affichage.
     */
    public function __construct(array $parameters = [], array $renderOptions = [])
    {
        !isset($parameters['size']) && $parameters['size'] = 1000;
        !isset($renderOptions['title']) && $renderOptions['title'] = __('Catégorie', 'docalist-search');
        parent::__construct('category', 'category', $parameters, $renderOptions);
    }
}
