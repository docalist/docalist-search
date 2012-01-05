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
 * Requête qui retourne les documents qui contiennent le terme unique qui
 * figure dans la requête.
 */
class TermQuery extends Query
{
    protected static $type = self::QUERY_TERM;

    public function __construct($term, $field = null)
    {
        if (! is_string($term))
        {
            var_export($term);
            throw new \Exception('Terme unique attendu.');
        }
        $this->args = array($term);
        $this->field = $field;
    }

    public function getTerm()
    {
        return $this->args[0];
    }
}