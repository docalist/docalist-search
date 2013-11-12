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

use Docalist\Data\Entity\AbstractEntity;

/**
 * Paramètres de l'indexeur.
 *
 * @property string[] $types Contenus à indexer.
 * @property int $bulkMaxSize Taille maximale du buffer (Mo).
 * @property int $bulkMaxCount Nombre maximum de documents dans le buffer.
 */
class IndexerSettings extends AbstractEntity {

    protected function loadSchema() {
        // @formatter:off
        return array(
            'types' => array(
                'label' => __('Contenus à indexer', 'docalist-search'),
                'type' => 'string*',
            ),
            'bulkMaxSize' => array(
                'label' =>__('Taille maximale du buffer (en Mo)', 'docalist-search'),
                'description' => __('En méga-octets. Le buffer est vidé si la taille totale des documents en attente dépasse cette limite.', 'docalist-search'),
                'type' => 'int',
                'default' => 10,
            ),
            'bulkMaxCount' => array(
                'label' =>__('Nombre maximum de documents', 'docalist-search'),
                'description' => __('Le buffer est vidé si le nombre de documents en attente dépasse ce nombre.', 'docalist-search'),
                'type' => 'int',
                'default' => 10000,
            ),
        );
        // @formatter:off
    }
}