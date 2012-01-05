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
 * Une clé de tri. Collection d'objets {@link SortkeyField}.
 */
class Sortkey extends NodesCollection
{
    protected static $defaultProperties = array
    (
        // Identifiant (numéro unique) de la clé de tri
    	'_id' => null,

        // Nom de la clé de tri
        'name' => '',

        // Libellé de l'index
        'label' => '',

        // Description de l'index
        'description' => '',

        // Type de la clé à créer ('string' ou 'number')
        'type' => array('string', 'number'),
    );

    protected static $validChildren = array('sortkeyfield');

    protected static $labels = array
    (
    	'main' => 'Clé de tri',
        'add' => "Nouvelle clé de tri",
        'remove' => "Supprimer la clé de tri %2", // %1=name, %2=type
    );

    protected static $icons = array
    (
        'image' => 'sort.png',
        'add' => 'sort--plus.png',
        'remove' => 'sort--minus.png',
    );
}