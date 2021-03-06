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
use Docalist\Search\Indexer\Field\PostStatusIndexer;
use stdClass;

/**
 * Une agrégation standard de type "terms" sur le champ "status" qui retourne le nombre de documents pour chacun
 * des statuts WordPress trouvés.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class TermsStatus extends TermsAggregation
{
    /**
     * Constructeur
     *
     * @param array $parameters     Autres paramètres de l'agrégation.
     * @param array $options        Options d'affichage.
     */
    public function __construct(array $parameters = [], array $options = [])
    {
        !isset($parameters['size']) && $parameters['size'] = 100;
        !isset($options['title']) && $options['title'] = __('Statut de publication', 'docalist-search');
        parent::__construct(PostStatusIndexer::CODE_FILTER, $parameters, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function getBucketLabel(stdClass $bucket): string
    {
        if ($status = get_post_status_object($bucket->key)) {
            return $status->label;
        }

        return $bucket->key;
    }
}
