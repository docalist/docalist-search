<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2012, 2013 Daniel Ménard
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

use Docalist\Data\Entity\AbstractSettingsEntity;

/**
 * Options de configuration du plugin.
 *
 * @property bool $enabled Indique si la recherche est activée.
 * @property ServerSettings $server Paramètres du serveur ElasticSearch.
 * @property IndexerSettings $indexer Paramètres de l'indexeur.
 * @property string[] $types Contenus à indexer.
 */
class Settings extends AbstractSettingsEntity {

    protected function loadSchema() {
        // @formatter:off
        return array(
            'enabled' => array(
                'type' => 'bool',
                'label' => __('Activer la recherche', 'docalist-search'),
                'default' => false,
            ),
            'server' => array(
                'label' => __('Serveur elasticsearch', 'docalist-search'),
                'type' => 'ServerSettings',
            ),
            'indexer' => array(
                'label' => __("Paramètres de l'indexeur", 'docalist-search'),
                'type' => 'IndexerSettings',
            ),
            'types' => array(
                'label' => __('Contenus à indexer', 'docalist-search'),
                'type' => 'string*',
            ),
        );
        // @formatter:on
    }
}