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
 * Un champ dans une clé de tri.
 */
class SortkeyField extends Node
{
    protected static $defaultProperties = array
    (
        // Identifiant (numéro unique) du champ
        '_id' => null,

        // Nom du champ
        'name' => '@field',

        // Position de début ou chaine délimitant le début de la valeur à ajouter à la clé
        'start' => '',

        // Longueur ou chaine délimitant la fin de la valeur à ajouter à la clé
        'end' => '',

        // Longueur totale de la partie de clé (tronquée ou paddée à cette taille)
        'length' => 0,
    );

    protected static $labels = array
    (
    	'main' => 'Champ de clé',
        'add' => "Ajouter un champ à la clé",
        'remove' => "Supprimer le champ %2 de la clé", // %1=name, %2=type
    );

    protected static $icons = array
    (
        'image' => 'zone.png',
        'add' => 'zone--plus.png',
        'remove' => 'zone--minus.png',
    );
}