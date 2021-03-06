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

use Docalist\Search\Aggregation\Bucket\TermsAggregation;
use Docalist\Search\Indexer\Field\PostTypeIndexer;

/**
 * Une agrégation standard de type "terms" sur le champ "type-label.filter".
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class TermsTypeLabel extends TermsAggregation
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
        !isset($options['title']) && $options['title'] = __('Type de document', 'docalist-search');
        parent::__construct(PostTypeIndexer::LABEL_FILTER, $parameters, $options);
    }
}
