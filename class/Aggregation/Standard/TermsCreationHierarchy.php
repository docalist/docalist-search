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

use Docalist\Search\Aggregation\Bucket\TermsAggregation;
use Docalist\Search\Indexer\Field\PostDateIndexer;

/**
 * Une facette hiérarchique de type "terms" sur le champ "creation-hierarchy" qui permet de sélectionner des posts
 * par date de création.
 *
 * La facette affiche d'abord l'année, puis le mois et enfin le jour.
 *
 * Elle permet de sélectionner plusieurs entrées (multiselect).
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class TermsCreationHierarchy extends TermsAggregation
{
    public function __construct(array $parameters = [], array $options = [])
    {
        !isset($parameters['size']) && $parameters['size'] = 1000;
        !isset($options['title']) && $options['title'] = __('Date du post', 'docalist-search');
        $parameters['order'] = ['_key' => 'desc']; // "_term" avant elasticsearch 6.0
        $options['hierarchy'] = true;
        $options['multiselect'] = true;
        parent::__construct(PostDateIndexer::HIERARCHY_FILTER, $parameters, $options);
    }
}