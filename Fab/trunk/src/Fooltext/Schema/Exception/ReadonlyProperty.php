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
namespace Fooltext\Schema\Exception;

class ReadonlyProperty extends SchemaException
{
    public function __construct($property = null, $code = 0, Exception $previous = null)
    {
        parent::__construct("Property $property is read-only", $code, $previous);
    }
}