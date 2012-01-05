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
 * Liste des clés de tri. Collection d'objets {@link Sortkey}.
 */
class Sortkeys extends NodesCollection
{
    protected static $defaultProperties = array
    (
        '_lastid' => null,
    );

    protected static $validChildren = array('sortkey');

    protected static $labels = array
    (
    	'main' => 'Clés de tri',
    );

    protected static $icons = array
    (
        'image' => 'sort--arrow.png',
    );
}