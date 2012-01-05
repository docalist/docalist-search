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
 * Un index dans un alias.
 */
class AliasIndex extends Node
{
    protected static $defaultProperties = array
    (
        // Identifiant (numéro unique) du champ
        '_id' => null,

        // Nom de l'index
        'name' => '@index',
    );

    protected static $labels = array
    (
    	'main' => 'Index',
        'add' => "Ajouter un index à l'alias",
        'remove' => "Supprimer l'index %2 de l'alias", // %1=name, %2=type
    );

    protected static $icons = array
    (
        'image' => 'lightning.png',
        'add' => 'lightning--plus.png',
        'remove' => 'lightning--minus.png',
    );
}