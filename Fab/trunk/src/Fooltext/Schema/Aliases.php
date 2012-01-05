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
 * Liste des alias. Collection d'objets {@link Alias}.
 */
class Aliases extends Nodes
{
    protected static $defaultProperties = array
    (
        '_lastid' => null,
    );

    protected static $validChildren = array('alias');

    protected static $labels = array
    (
    	'main' => 'Liste des alias',
    );

    protected static $icons = array
    (
        'image' => 'key--arrow.png',
    );
}