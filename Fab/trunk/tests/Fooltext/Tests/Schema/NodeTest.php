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

use Fooltext\Schema\Node;
use Fooltext\Schema\Collection;

/**
 * Node est une base abstraite, pour pouvoir faire les tests,
 * on créer une classe concrête
 */
class MyNode extends Node
{
    protected static $knownProperties = array
    (
        'a' => array('default' => 'A'),
        'b' => array('default' => 'B'),
        'c' => array(),
    );
    protected static $labels = array
    (
        'my' => 'mylabel',
    );

    protected static $icons = array
    (
        'my' => 'myicon',
    );

    protected function setToto($value)
    {
        $this->properties['toto'] = 'ToTo';
    }
    public static function getDefaultValue($name = null)
    {
        return parent::getDefaultValue($name);
    }

    public static function checkStatics()
    {
//         var_export(static::$labels);
//         var_export(self::$labels);
//         var_export(parent::$labels);
//         die();
        return
              parent::getKnownProperties() === self::$knownProperties

        &&    parent::getDefaultValue('a') === 'A'
        &&    parent::getDefaultValue('b') === 'B'
        &&    parent::getDefaultValue('c') === null
        &&    parent::getDefaultValue('z') === null

        &&    parent::getLabels() === parent::$labels + self::$labels
        &&    parent::getLabels('main')  === parent::$labels['main']
        &&    parent::getLabels('xyz')  === 'xyz'
        &&    parent::getLabels('my')  === 'mylabel'

        &&    parent::getIcons() === parent::$icons + self::$icons
        &&    parent::getIcons('add')  === parent::$icons['add']
        &&    parent::getIcons('xyz')  === null
        &&    parent::getIcons('my')  === 'myicon'
        ;

    }

    public function _toJson($indent = false, $currentIndent = '', $colon = ':')
    {
        return parent::_toJson($indent, $currentIndent, $colon);
    }

    public function _toXml(\XMLWriter $xml)
    {
        return parent::_toXml($xml);
    }

    public function toXml($indent = false)
    {
        $xml = new \XMLWriter();
        $xml->openMemory();
        if ($indent === true) $indent = 4; else $indent=(int) $indent;
        if ($indent > 0)
        {
            $xml->setIndent(true);
            $xml->setIndentString(str_repeat(' ', $indent));
        }
        $xml->startDocument('1.0', 'utf-8', 'yes');

        $xml->startElement('node');
        parent::_toXml($xml);
        $xml->endElement();

        $xml->endDocument();
        return $xml->outputMemory(true);
    }


}

class NodeTest extends \PHPUnit_Framework_TestCase
{
    protected $data = array
    (
        'b1' => true,
        'b2' => false,
    	'i' => 12,
    	'f' => 3.14,
    	's' => 'string',
    	'l' => 'L',
    	'array' => array(true,12,3.14,'string')
    );

    public function testJson()
    {
        // Création d'un noeud vide
        $node = new MyNode();
        $this->assertEquals
        (
        	'',
            $node->_toJson()
        );

        // Création d'un noeud vide + une propriété par défaut modifiée
        $node = new MyNode();
        $node->a = 'AA';
        $this->assertEquals
        (
        	'"a":"AA"',
            $node->_toJson()
        );

        // Création d'un noeud avec des propriétés initiales
        $node = new MyNode($this->data);

        $this->assertEquals
        (
            '"b1":true,"b2":false,"i":12,"f":3.14,"s":"string","l":"L","array":[true,12,3.14,"string"]',
            $node->_toJson()
        );
    }

    public function testXml()
    {
        // Création d'un noeud vide
        $node = new MyNode();
        $this->assertXmlStringEqualsXmlString
        (
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><node/>',
            $node->toXml()
        );

        // Création d'un noeud vide + une propriété par défaut modifiée
        $node = new MyNode();
        $node->a = 'AA';
        $this->assertXmlStringEqualsXmlString
        (
			'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
			<node>
				<a>AA</a>
			</node>',
            $node->toXml()
        );

        // Création d'un noeud avec des propriétés initiales
        $node = new MyNode($this->data);

        $this->assertXmlStringEqualsXmlString
        (
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <node>
            	<b1>true</b1>
            	<b2>false</b2>
            	<i>12</i>
            	<f>3.14</f>
            	<s>string</s>
            	<l>L</l>
            	<array>
            		<item>1</item>
            		<item>12</item>
            		<item>3.14</item>
            		<item>string</item>
        		</array>
    		</node>',
            $node->toXml()
        );

    }

    public function testEmptyNode()
    {
        $this->assertTrue(MyNode::checkStatics());

        // Création d'un noeud vide
        $node = new MyNode();

        $this->assertEquals($node->getProperties(), array
        (
            'a' => 'A',
            'b' => 'B',
            'c' => null,
        ));

        $this->assertEquals($node->a, 'A');
        $this->assertEquals($node->b, 'B');
        $this->assertEquals($node->c, null);
        $this->assertEquals($node->x, null);

        $this->assertTrue(isset($node->a));
        $this->assertTrue(isset($node->b));
        $this->assertTrue(isset($node->c)); // vaut null mais existe
        $this->assertFalse(isset($node->x));

        // Création d'un noeud avec des propriétés initiales
        $node = new MyNode(array('b' => 'BB', 'c' => 'CC', 'k' => 'K', 'l' => 'L'));

        $this->assertEquals($node->getProperties(), array
        (
            'a' => 'A',
            'b' => 'BB',
            'c' => 'CC',
            'k' => 'K',
            'l' => 'L',
        ));

        $this->assertEquals($node->a, 'A');
        $this->assertEquals($node->b, 'BB');
        $this->assertEquals($node->c, 'CC');
        $this->assertEquals($node->k, 'K');
        $this->assertEquals($node->l, 'L');
        $this->assertEquals($node->x, null);

        $this->assertTrue(isset($node->a));
        $this->assertTrue(isset($node->b));
        $this->assertTrue(isset($node->c));
        $this->assertTrue(isset($node->k));
        $this->assertTrue(isset($node->l));
        $this->assertFalse(isset($node->x));

        $this->assertEquals('"b":"BB","c":"CC","k":"K","l":"L"', $node->_toJson());

        // Modification des propriétés
        $node->a = 'AAAAAAAA';
        $this->assertTrue(isset($node->a));
        $this->assertEquals($node->a, 'AAAAAAAA');

        unset($node->a);
        $this->assertTrue(isset($node->a)); // 'a' a repris sa valeur par défaut lors du unset
        $this->assertEquals($node->a, 'A');

        $node->a = 'AAAAAAAA';
        $this->assertTrue(isset($node->a));
        $this->assertEquals($node->a, 'AAAAAAAA');

        unset($node->l);
        $this->assertFalse(isset($node->l));// pas de prop par défaut, réellement unset
        $this->assertEquals($node->l, null);

        $node->a = null;
        $this->assertTrue(isset($node->a)); // 'a' a repris sa valeur par défaut lors du unset
        $this->assertEquals($node->a, 'A');

        $node->zz = 'ZZ';
        $this->assertTrue(isset($node->zz));
        $this->assertEquals($node->zz, 'ZZ');

        unset($node->zz);
        $this->assertFalse(isset($node->zz));
        $this->assertEquals($node->zz, null);

        $node->toto = 'aaa';
        $this->assertTrue(isset($node->toto));
        $this->assertEquals($node->toto, 'ToTo'); // MyNode::setToto() a été appellé et a écrasé la valeur

        unset($node->toto);
        $this->assertFalse(isset($node->toto));

        $this->assertNull($node->parent);
        $this->assertNull($node->schema);

        $col = new Collection();
        $node->parent = $col;
        $this->assertSame($col, $node->parent);
    }
}