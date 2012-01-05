<?php
/**
 * This file is part of the Fooltext package.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Fooltext
 * @subpackage  Tests
 * @author      Daniel Ménard <Daniel.Menard@laposte.net>
 * @version     SVN: $Id$
 */
namespace Fooltext\Tests;

use Fooltext\Query\Query;
use Fooltext\Query\OrQuery;
use Fooltext\Query\AndQuery;
use Fooltext\Query\TermQuery;

class QueryTest extends \PHPUnit_Framework_TestCase
{
    public function test1()
    {
        $this->assertSame('a', (string)new TermQuery('a'));
//         $or = new OrQuery(null, 'a');
//         echo "here";
//         var_dump($or);
//         $this->assertSame('a', (string) $or);
//         $this->assertSame(array('a'), $or->getArgs());

        $or1 = new OrQuery(array('a', 'b'));
        $or2 = new OrQuery(array('c', 'd'));
        $or3 = new OrQuery(array('e', 'f'));
        $this->assertSame('(a OR b)', (string)$or1);
        $this->assertSame(array('a','b'), $or1->getArgs());

        $and1 = new AndQuery(array('A', 'B'));
        $and2 = new AndQuery(array('C', 'D'));
        $and3 = new AndQuery(array('E', 'F'));
        $this->assertSame('(A AND B)', (string)$and1);
        $this->assertSame(array('A','B'), $and1->getArgs());

        $this->assertSame('((a OR b) OR (c OR d))', (string)new OrQuery(array($or1, $or2)));
        $this->assertSame('((a OR b) OR (c OR d) OR (e OR f))', (string)new OrQuery(array($or1, $or2, $or3)));

//         $this->assertSame('(a OR b OR c OR d)', (string)new Query(Query::QUERY_OR, $or1, $or2));
    }
}