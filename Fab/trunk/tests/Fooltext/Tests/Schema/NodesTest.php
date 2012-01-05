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

use Fooltext\Schema\Nodes;

/**
 * Node est une base abstraite, pour pouvoir faire les tests,
 * on créer une classe concrête
 */
class MyNodes extends Nodes
{
    protected static $knownProperties = array
    (
        'a' => array('default' => 'A'),
    );

    protected $myProp1 = 'prop1';
    protected $myProp2;

    protected function getMyProp2()
    {
        return $this->myProp2 . 'Get';
    }

    protected function setMyProp2($value)
    {
        $this->myProp2 = 'prop2' . $value;
    }

}

class NodesTest extends \PHPUnit_Framework_TestCase
{
    public function test1()
    {
        // Création d'un noeud vide
        $node = new MyNodes();

        $this->assertEquals($node->a, 'A');
        $this->assertEquals($node->myProp1, 'prop1');
        $this->assertEquals($node->myProp2, 'Get');
        $this->assertEquals($node->z, null);

        $node->a = 'AA';
        $this->assertEquals($node->a, 'AA');
        $this->assertTrue(isset($node->a));

        $this->assertTrue(isset($node->myProp1));
        $this->assertTrue(isset($node->myProp2));

        $node->z = 'Z';
        $this->assertEquals($node->z, 'Z');

        $node->a = null;
        $this->assertEquals($node->a, 'A');
    }

	/**
     * @expectedException Fooltext\Schema\Exception\ReadonlyProperty
     */
    public function testSetProtectedProperty1()
    {
        // Création d'un noeud vide
        $node = new MyNodes();

        $node->myProp1 = 'PROP1';
    }

    public function testSetProtectedProperty2()
    {
        // Création d'un noeud vide
        $node = new MyNodes();

        $node->myProp2 = 'PROP2';
        $this->assertEquals($node->myProp2, 'prop2PROP2Get');

    }

	/**
     * @expectedException Fooltext\Schema\Exception\ReadonlyProperty
     */
    public function testUnsetProtectedProperty1()
    {
        // Création d'un noeud vide
        $node = new MyNodes();

        unset($node->myProp1);
    }

    public function testUnsetProtectedProperty2()
    {
        // Création d'un noeud vide
        $node = new MyNodes();

        unset($node->myProp2);
        $this->assertEquals($node->myProp2, 'prop2Get');
    }

}