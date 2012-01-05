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
 * Liste des tables de lookup. Collection d'objets {@link LookupTable}.
 */
class LookupTables extends NodesCollection
{
    protected static $defaultProperties = array
    (
        '_lastid' => null,
    );
    protected static $validChildren = array('lookuptable');

    protected static $labels = array
    (
    	'main' => 'Tables de lookup',
    );

    protected static $icons = array
    (
        'image' => 'magnifier--arrow.png',
    );
}