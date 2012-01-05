<?php
/**
 * This file is part of the Fooltext package.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Fooltext
 * @subpackage  Schema
 * @author      Daniel Ménard <Daniel.Menard@laposte.net>
 * @version     SVN: $Id$
 */
namespace Fooltext\Schema;

/**
 * Un champ comportant plusieurs sous zones. Collection d'objets {@link Field}.
 */
class GroupField extends NodesCollection
{
    protected static $defaultProperties = array
    (
        // Identifiant (numéro unique) du champ
		'_id' => null,

        // Nom du champ, d'autres noms peuvent être définis via des alias
        'name' => '',

        // Libellé du champ
        'label' => '',

        // Description
        'description' => '',
    );

    protected static $validChildren = array('field');

    protected static $labels = array
    (
        'main' => 'Groupe de champs',
        'add' => 'Nouveau groupe de champs',
        'remove' => 'Supprimer le groupe de champs %2', // %1=name, %2=type
    );


    protected static $icons = array
    (
        'image' => 'folde-open-document-text.png',
        'add' => 'zone--plus.png', // todo
        'remove' => 'zone--minus.png', // todo
    );
}