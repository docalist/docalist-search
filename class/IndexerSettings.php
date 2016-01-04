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
 */
namespace Docalist\Search;

use Docalist\Type\Composite;
use Docalist\Type\Text;
use Docalist\Type\Integer;
use Docalist\Type\Boolean;

/**
 * Paramètres de l'indexeur.
 *
 * @property Text[]     $types          Contenus à indexer.
 * @property Integer    $bulkMaxSize    Taille maximale du buffer (Mo).
 * @property Integer    $bulkMaxCount   Nombre maximum de documents dans le buffer.
 * @property Boolean    $realtime       Indexation en temps réel activée.
 */
class IndexerSettings extends Composite
{
    static public function loadSchema()
    {
        return [
            'fields' => [
                'types' => [
                    'type' => 'Docalist\Type\Text*',
                    'label' => __('Contenus à indexer', 'docalist-search'),
                ],
                'bulkMaxSize' => [
                    'type' => 'Docalist\Type\Integer',
                    'label' => __('Taille maximale du buffer', 'docalist-search'),
                    'description' => __('En méga-octets. Le buffer est vidé si la taille totale des documents en attente dépasse cette limite.', 'docalist-search'),
                    'default' => 10,
                ],
                'bulkMaxCount' => [
                    'type' => 'Docalist\Type\Integer',
                    'label' => __('Nombre maximum de documents', 'docalist-search'),
                    'description' => __('Le buffer est vidé si le nombre de documents en attente dépasse ce nombre.', 'docalist-search'),
                    'default' => 10000,
                ],
                'realtime' => [
                    'type' => 'Docalist\Type\Boolean',
                    'label' => __('Indexation en temps réel', 'docalist-search'),
                    'description' => __('Réindexer automatiquement les contenus créés ou modifiés et retirer les contenus supprimés.', 'docalist-search'),
                    'default' => false,
                ],
            ],
        ];
    }
}
