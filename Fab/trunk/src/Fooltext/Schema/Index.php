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
 * Un index. Collection d'objets {@link IndexField}.
 */
class Index extends NodesCollection
{
    protected static $defaultProperties = array
    (
        // Identifiant (numéro unique) de l'index
        '_id' => null,

        // Nom de l'index
        'name' => '',

        // Libellé de l'index
        'label' => '',

        // Description de l'index
        'description' => '',

        // Type d'index : 'probabilistic' ou 'boolean'
        'type' => array('probabilistic', 'boolean'),

        // Traduction de la propriété type en entier
        '_type' => null,

        // Ajouter les termes de cet index dans le correcteur orthographique
        'spelling' => false,
    );
    protected static $validChildren = array('indexfield');

    protected static $labels = array
    (
    	'main' => 'Index',
        'add' => 'Nouvel index',
        'remove' => "Supprimer l'index %2", // %1=name, %2=type
    );

    protected static $icons = array
    (
        'image' => 'lightning.png',
        'add' => 'lightning--plus.png',
        'remove' => 'lightning--minus.png',
    );
}