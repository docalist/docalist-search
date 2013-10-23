<?php
/**
 * This file is part of the 'Docalist Biblio' plugin.
 *
 * Copyright (C) 2012, 2013 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Biblio
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     $Id$
 */
namespace Docalist\Table;

use Docalist\Data\Entity\AbstractEntity;

/**
 * Paramètres d'une table d'autorité.
 *
 * @property string $name Nom de la table
 * @property string $path Path (absolu) de la table
 * @property string $label Libellé de la table
 * @property string $type Type de la table
 * @property bool $user true : table utilisateur, false : table prédéfinie
 */
class TableInfo extends AbstractEntity {

    protected function loadSchema() {
        // @formatter:off
        return array(
            'name' => array(
                'label' => __('Nom', 'docalist-core'),
                'description' => __('Nom de code de la table (doit être unique)', 'docalist-core'),
            ),

            'path' => array(
                'label' => __('Path', 'docalist-core'),
                'description' => __('Path (absolu) de la table.', 'docalist-core'),
            ),

            'label' => array(
                'label' => __('Libellé', 'docalist-core'),
                'description' => __('Libellé de la table', 'docalist-core'),
            ),

            'type' => array(
                //'default' => $this->name,
                'label' => __('Type', 'docalist-core'),
                'description' => __('Type de table.', 'docalist-core'),
            ),

            'user' => array(
                'type' => 'bool',
                'default' => true,
                'label' => __('Table utilisateur', 'docalist-core'),
                'description' => __("Indique s'il s'agit d'une table utilisateur ou d'une table prédéfinie.", 'docalist-core'),
            ),
        );
        // @formatter:on
    }
}