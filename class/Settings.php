<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package Docalist
 * @subpackage Search
 * @author Daniel Ménard <daniel.menard@laposte.net>
 * @version SVN: $Id$
 */
namespace Docalist\Search;

use Docalist\Type\Settings as TypeSettings;
use Docalist\Type\Integer;
use Docalist\Type\Boolean;

/**
 * Options de configuration du plugin.
 *
 * @property Integer $searchpage ID de la page "liste des réponses".
 * @property Boolean $enabled Indique si la recherche est activée.
 * @property ServerSettings $server Paramètres du serveur ElasticSearch.
 * @property IndexerSettings $indexer Paramètres de l'indexeur.
 */
class Settings extends TypeSettings {
    protected $id = 'docalist-search-settings';

    static protected function loadSchema() {
        // @formatter:off
        return [
            'fields' => [
                'searchpage' => [
                    'type' => 'int',
                    'label' =>__('Page liste des réponses', 'docalist-search'),
                    'description' => __("Page WordPress sur laquelle sont affichées les réponses obtenues.", 'docalist-search'),
                ],
                'enabled' => [
                    'type' => 'bool',
                    'label' => __('Recherche Docalist Search', 'docalist-search'),
                    'description' => __("Activer la recherche Docalist Search.", 'docalist-search'),
                    'default' => false,
                ],
                'server' => [
                    'label' => __('Serveur elasticsearch', 'docalist-search'),
                    'type' => 'ServerSettings',
                ],
                'indexer' => [
                    'label' => __("Paramètres de l'indexeur", 'docalist-search'),
                    'type' => 'IndexerSettings',
                ]
            ]
        ];
        // @formatter:on
    }
}