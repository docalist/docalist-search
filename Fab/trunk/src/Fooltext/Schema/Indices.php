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
 * Liste des index. Collection d'objets {@link Index}.
 */
class Indices extends NodesCollection
{
    protected static $defaultProperties = array
    (
        '_lastid' => null,
    );

    protected static $validChildren = array('index');

    protected static $labels = array
    (
        'main' => 'Liste des index',
    );

    protected static $icons = array
    (
        'image' => 'lightning--arrow.png',
    );
}