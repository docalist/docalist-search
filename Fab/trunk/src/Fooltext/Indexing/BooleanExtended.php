<?php
/**
 * This file is part of the Fooltext package.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Fooltext
 * @subpackage  Indexing
 * @author      Daniel Ménard <Daniel.Menard@laposte.net>
 * @version     SVN: $Id$
 */
namespace Fooltext\Indexing;

class BooleanExtended extends Boolean
{
    /**
     * Termes à générer si le booléen est à true.
     *
     * @var mixed
     */
    protected static $true = array('true', 'on', '1', 'vrai');

    /**
     * Termes à générer si le booléen est à false.
     *
     * @var mixed
     */
    protected static $false = array('false', 'off', '0', 'faux');
}