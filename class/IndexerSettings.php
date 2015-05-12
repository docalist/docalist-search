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

use Docalist\Type\Object;
use Docalist\Type\Integer;

/**
 * Paramètres de l'indexeur.
 *
 * @property String[] $types Contenus à indexer.
 * @property Integer $bulkMaxSize Taille maximale du buffer (Mo).
 * @property Integer $bulkMaxCount Nombre maximum de documents dans le buffer.
 */
class IndexerSettings extends Object {
    static protected function loadSchema() {
        // @formatter:off
        return [
            'fields' => [
                'types' => [
                    'label' => __('Contenus à indexer', 'docalist-search'),
                    'type' => 'string*',
                ],
                'bulkMaxSize' => [
                    'label' =>__('Taille maximale du buffer', 'docalist-search'),
                    'description' => __('En méga-octets. Le buffer est vidé si la taille totale des documents en attente dépasse cette limite.', 'docalist-search'),
                    'type' => 'int',
                    'default' => 10,
                ],
                'bulkMaxCount' => [
                    'label' =>__('Nombre maximum de documents', 'docalist-search'),
                    'description' => __('Le buffer est vidé si le nombre de documents en attente dépasse ce nombre.', 'docalist-search'),
                    'type' => 'int',
                    'default' => 10000,
                ],
                'realtime' => [
                    'type' => 'bool',
                    'label' => __("Indexation en temps réel", 'docalist-search'),
                    'description' => __("Réindexer automatiquement les contenus créés ou modifié et enlève de l'index les contenus supprimés.", 'docalist-search'),
                    'default' => false,
                ],
            ]
        ];
        // @formatter:off
    }
}