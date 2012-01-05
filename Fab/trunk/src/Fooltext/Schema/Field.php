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
 * Un champ simple.
 */
class Field extends Node
{
    protected static $defaultProperties = array
    (
        // Nom du champ, d'autres noms peuvent être définis via des alias
        'name' => '',

        // Identifiant (numéro unique) du champ
		'_id' => null,

        // Type du champ
        'type' => array('text','bool','int','autonumber'),

        // Traduction de la propriété type en entier
        '_type' => null,

        // Libellé du champ
        'label' => '',

        // Description
        'description' => '',

        // Faut-il utiliser les mots-vides de la base
//         'defaultstopwords' => true,

        // Liste spécifique de mots-vides à appliquer à ce champ
//         'stopwords' => '',

    //        'widget' => array('display'),

    	'widget' => array('textbox', 'textarea', 'checklist', 'radiolist', 'select'),
    	'datasource' => array('pays','langues','typdocs'),

    	'analyzer' => array('DefaultMapper', 'HtmlMapper'),

    	'weight' => 1,
    );

    protected static $labels = array
    (
        'main' => 'Champ',
        'add' => 'Nouveau champ',
        'remove' => 'Supprimer le champ %2', // %1=name, %2=type
    );

    protected static $icons = array
    (
        'image' => 'zone.png',
        'add' => 'zone--plus.png',
        'remove' => 'zone--minus.png',
    );
}