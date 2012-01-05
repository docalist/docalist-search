<?php
/**
 * This file is part of the Fooltext package.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Fooltext
 * @subpackage  Query
 * @author      Daniel Ménard <Daniel.Menard@laposte.net>
 * @version     SVN: $Id$
 */
namespace Fooltext\Query;

/**
 * Requête qui retourne les documents qui contiennent tous les arguments.
 */
class AndQuery extends BooleanQuery
{
    protected static $type = self::QUERY_AND;
}