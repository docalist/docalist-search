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
 * Un champ indexé.
 */
class IndexField extends Node
{
    protected static $defaultProperties = array
    (
        // Identifiant du champ
        '_id' => null,

        // Nom du champ
        'name' => '@field',

        // Indexer les mots
        'words' => true,

        // Indexer les phrases
        'phrases' => false,

        // Indexer les valeurs
        'values' => false,

        // Compter le nombre de valeurs (empty, has1, has2...)
        'count' => false,

        // DEPRECATED : n'est plus utilisé, conservé pour compatibilité
        'global' => false,

        // Position ou chaine indiquant le début du texte à indexer
        'start' => '',

        // Position ou chain indiquant la fin du texte à indexer
        'end' => '',

        // Poids des tokens ajoutés à cet index
        'weight' => 1
    );

    protected static $labels = array
    (
    	'main' => 'Champ indexé',
        'add' => "Ajouter un champ dans l'index",
        'remove' => "Supprimer le champ %2 de l'index", // %1=name, %2=type
    );

    protected static $icons = array
    (
        'image' => 'zone.png',
        'add' => 'zone--plus.png',
        'remove' => 'zone--minus.png',
    );
}