<?php
/**
 * This file is part of the Fooltext package.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Fooltext
 * @subpackage  Store
 * @author      Daniel Ménard <Daniel.Menard@laposte.net>
 * @version     SVN: $Id$
 */
namespace Fooltext\Store\Exception;

/**
 * Exception générée si on demande à charger un document qui ne figure pas
 * dans la base.
 *
 */
class DocumentNotFound extends \OutOfBoundsException
{

}