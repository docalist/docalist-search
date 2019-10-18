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
use WP_User;
use stdClass;
use Docalist\Search\Indexer\Field\PostAuthorIndexer;

/**
 * Une agrégation standard de type "terms" sur le champ "createdby" qui retourne le nombre de documents pour
 * chacun des utilisateurs WordPress qui a créé des posts ou des notices.
 *
 * La facette permet de sélectionner plusieurs entrées (multiselect).
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class TermsCreatedBy extends TermsAggregation
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
        !isset($options['title']) && $options['title'] = __('Auteur du post', 'docalist-search');
        !isset($options['multiselect']) && $options['multiselect'] = true;
        parent::__construct(PostAuthorIndexer::LOGIN_FILTER, $parameters, $options);
    }

    public function getBucketLabel(stdClass $bucket): string
    {
        $user = get_user_by('login', $bucket->key); /** @var WP_User|false $user */

        return  $user ? $user->display_name : $bucket->key;
    }
}
