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
 * Requête qui ne retourne jamais aucun document.
 *
 * @var int
 */
class MatchNothingQuery extends Query
{
    protected static $type = self::QUERY_MATCH_NOTHING;

    public function __construct($field = null)
    {
        $this->args = array();
        $this->field = $field;
    }

    public function __toString()
    {
        return 'null';
   }
}