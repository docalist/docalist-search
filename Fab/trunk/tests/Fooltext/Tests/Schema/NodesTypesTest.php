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

use Fooltext\Schema\NodesTypes;

class ClassForNewType extends \Fooltext\Schema\Node {};
class BadClassForNewType extends \ArrayObject {};

class NodesTypesTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultMap()
    {
        $this->assertEquals
        (
            NodesTypes::all(),
            array
            (
            	'schema'               => 'Fooltext\Schema\Schema',
            	'collection'           => 'Fooltext\Schema\Collection',
            	'fields'               => 'Fooltext\Schema\Fields',
                    'field'            => 'Fooltext\Schema\Field',
                    'groupfield'       => 'Fooltext\Schema\GroupField',
            	'indices'              => 'Fooltext\Schema\Indices',
            		'index'            => 'Fooltext\Schema\Index',
                	'indexfield'       => 'Fooltext\Schema\IndexField',
            	'aliases'              => 'Fooltext\Schema\Aliases',
                	'alias'	           => 'Fooltext\Schema\Alias',
            		'aliasindex'       => 'Fooltext\Schema\AliasIndex',
            	'lookuptables'         => 'Fooltext\Schema\LookupTables',
                	'lookuptable'      => 'Fooltext\Schema\LookupTable',
                	'lookuptablefield' => 'Fooltext\Schema\LookupTableField',
                'sortkeys'	           => 'Fooltext\Schema\Sortkeys',
            		'sortkey'	       => 'Fooltext\Schema\Sortkey',
                	'sortkeyfield'     => 'Fooltext\Schema\SortkeyField',
            )
        );
    }

    public function testRegister()
    {
        NodesTypes::register('newtype', 'Fooltext\Tests\ClassForNewType');
    }

	/**
     * @expectedException \Fooltext\Schema\Exception\BadClass
     */
    public function testBadRegister()
    {
        NodesTypes::register('badnewtype', 'Fooltext\Tests\BadClassForNewType');
    }

	/**
     * @expectedException \Fooltext\Schema\Exception\ClassNotFound
     */
    public function testBadRegister2()
    {
        NodesTypes::register('badnewtype2', 'Fooltext\Tests\InexistantClass');
    }

    public function testTypeToClass()
    {
        $this->assertEquals(NodesTypes::nodetypeToClass('schema'), 'Fooltext\Schema\Schema');
        $this->assertEquals(NodesTypes::nodetypeToClass('field'), 'Fooltext\Schema\Field');
        $this->assertEquals(NodesTypes::nodetypeToClass('newtype'), 'Fooltext\Tests\ClassForNewType');
    }

    public function testClassToType()
    {
        $this->assertEquals(NodesTypes::classToNodetype('Fooltext\Schema\Schema'), 'schema');
        $this->assertEquals(NodesTypes::classToNodetype('Fooltext\Schema\Field'), 'field');
    }

	/**
     * @expectedException \Fooltext\Schema\Exception\ClassNotFound
     */
    public function testUnknownClass()
    {
        NodesTypes::classToNodetype('Fooltext\Schema\Inexistant');
    }

	/**
     * @expectedException \Fooltext\Schema\Exception\BadNodeType
     */
    public function testInvalidType()
    {
        NodesTypes::nodetypeToClass('non-existant-type');
    }

	/**
     * @expectedException \Fooltext\Schema\Exception\BadNodeType
     */
    public function testInvalidType2()
    {
        NodesTypes::nodetypeToClass('badnewtype');
    }

}