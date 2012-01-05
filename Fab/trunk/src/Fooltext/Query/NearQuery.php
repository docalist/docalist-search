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
 * Requête qui retourne les documents qui contiennent les arguments indiqués
 * dans n'importe quel ordre mais à une certaine distance maximale les uns des autres.
 */
class NearQuery extends PositionalQuery
{
    protected static $type = self::QUERY_NEAR;
}