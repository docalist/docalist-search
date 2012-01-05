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
 * Liste des champs. Collection d'objets {@link Field}.
 */
class Fields extends Nodes
{
    protected static $defaultProperties = array
    (
        '_lastid' => null,
    );

    protected static $validChildren = array('field', 'groupfield');

    protected static $labels = array
    (
        'main' => 'Liste des champs',
    );

    protected static $icons = array
    (
        'image' => 'zone--arrow.png',
    );
}
