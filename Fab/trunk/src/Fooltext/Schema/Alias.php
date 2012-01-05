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
 * Un alias. Collection d'objets {@link AliasIndex}.
 */
class Alias extends NodesCollection
{
    protected static $defaultProperties = array
    (
        // Identifiant (numéro unique) de l'alias (non utilisé)
    	'_id' => null,

        // Nom de l'alias
        'name' => '',

        // Libellé de l'index
        'label' => '',

        // Description de l'index
        'description' => '',

        // Type d'index : 'probabilistic' ou 'boolean'
        'type' => array('probabilistic', 'boolean'),

        // Traduction de la propriété type en entier
        '_type' => null,
    );

    protected static $validChildren = array('aliasindex');

    protected static $labels = array
    (
    	'main' => 'Alias',
        'add' => "Nouvel alias",
        'remove' => "Supprimer l'alias %2", // %1=name, %2=type
    );

    protected static $icons = array
    (
        'image' => 'key.png',
        'add' => 'key--plus.png',
        'remove' => 'key--minus.png',
    );
}