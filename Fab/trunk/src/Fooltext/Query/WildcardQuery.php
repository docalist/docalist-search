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
 * Requête qui retourne les documents qui contiennent au moins un terme
 * correspondant au masque qui figure dans la requête.
 */
class WildcardQuery extends TermQuery
{
    protected static $type = self::QUERY_WILDCARD;
}