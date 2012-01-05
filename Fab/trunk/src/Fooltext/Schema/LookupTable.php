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
 * Une table de lookup. Collection d'objets {@link LookupTableField}.
 */
class LookupTable extends NodesCollection
{
    protected static $defaultProperties = array
    (
        // Identifiant (numéro unique) de la table
    	'_id' => null,

        // Nom de la table
        'name' => '',

        // Libellé de l'index
        'label' => '',

        // Description de l'index
        'description' => '',

        // type de table : "simple" ou "inversée"
        'type' => array('simple'), // 'inverted' n'est plus utilisé

        // Traduction de type en entier
        // '_type'=>self::LOOKUP_SIMPLE,
    );

    protected static $validChildren = array('lookuptablefield');

    protected static $labels = array
    (
    	'main' => 'Table de lookup',
        'add' => "Nouvelle table de lookup",
        'remove' => "Supprimer la table de lookup", // %1=name, %2=type
    );

    protected static $icons = array
    (
        'image' => 'magnifier.png',
        'add' => 'magnifier--plus.png',
        'remove' => 'magnifier--minus.png',
    );
}