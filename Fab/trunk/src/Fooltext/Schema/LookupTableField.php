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
 * Un champ dans un table de lookup.
 */
class LookupTableField extends Node
{
    protected static $defaultProperties = array
    (
        // Identifiant (numéro unique) du champ
    	'_id' => null,

        // Nom du champ
        'name' => '@field',

        // Indice du premier article à prendre en compte (1-based)
        'startvalue' => 1,

        // Indice du dernier article à prendre en compte (0=jusqu'à la fin)
        'endvalue' => 0,

        // Position de début ou chaine délimitant le début de la valeur à ajouter à la table
        'start' => '',

        // Longueur ou chaine délimitant la fin de la valeur à ajouter à la table
        'end' => ''
    );

    protected static $labels = array
    (
    	'main' => 'Champ de table',
        'add' => "Ajouter un champ à la table",
        'remove' => "Supprimer le champ %2 de la table", // %1=name, %2=type
    );

    protected static $icons = array
    (
        'image' => 'zone.png',
        'add' => 'zone--plus.png',
        'remove' => 'zone--minus.png',
    );
}