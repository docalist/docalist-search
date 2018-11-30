<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2013-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search\Aggregation\Standard;

use Docalist\Search\Aggregation\Bucket\TermsAggregation;

/**
 * Une facette hiérarchique de type "terms" sur le champ "creation-hierarchy" qui permet de sélectionner des posts
 * par date de création.
 *
 * La facette affiche d'abord l'année, puis le mois et enfin le jour.
 *
 * Elle permet de sélectionner plusieurs entrées (multiselect).
 */
class TermsCreationHierarchy extends TermsAggregation
{
    public function __construct(array $parameters = [], array $options = [])
    {
        !isset($parameters['size']) && $parameters['size'] = 1000;
        !isset($options['title']) && $options['title'] = __('Date du post', 'docalist-search');
        $parameters['order'] = ['_term' => 'desc'];
        $options['hierarchy'] = true;
        $options['multiselect'] = true;
        parent::__construct('creation-hierarchy', $parameters, $options);
    }
}