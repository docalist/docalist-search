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
use WP_User;
use stdClass;

/**
 * Une agrégation standard de type "terms" sur le champ "createdby" qui retourne le nombre de documents pour
 * chacun des utilisateurs WordPress qui a créé des posts ou des notices.
 *
 * La facette permet de sélectionner plusieurs entrées (multiselect).
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
        parent::__construct('createdby', $parameters, $options);
    }

    public function getBucketLabel(stdClass $bucket)
    {
        $user = get_user_by('login', $bucket->key); /** @var WP_User|false $user */

        return  $user ? $user->display_name : $bucket->key;
    }
}
