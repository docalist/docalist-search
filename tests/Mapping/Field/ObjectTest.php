<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2012-2016 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search\Tests\Mapping\Field;

use WP_UnitTestCase;
use Docalist\Search\Mapping\Field;
use Docalist\Search\Mapping\Field\Object;
use Docalist\Search\Mapping\Field\Binary;
use Docalist\Search\Mapping\Field\Boolean;
use Docalist\Search\Mapping\Field\Date;
use Docalist\Search\Mapping\Field\Decimal;
use Docalist\Search\Mapping\Field\Geopoint;
use Docalist\Search\Mapping\Field\Geoshape;
use Docalist\Search\Mapping\Field\Integer;
use Docalist\Search\Mapping\Field\IP;
use Docalist\Search\Mapping\Field\Keyword;
use Docalist\Search\Mapping\Field\Nested;
use Docalist\Search\Mapping\Field\Text;

class ObjectTest extends WP_UnitTestCase
{
    public function testDefaultParameters()
    {
        $field = new Object('field');
        $this->assertSame([
            'type' => 'object',
//            'properties' => [],
        ], $field->getDefaultParameters());
    }

    public function testDefaultAnalyzer()
    {
        $field = new Object('field');
        $this->assertSame('text', $field->getDefaultTextAnalyzer());

        $this->assertSame($field, $field->setDefaultTextAnalyzer('fr-text'));
        $this->assertSame('fr-text', $field->getDefaultTextAnalyzer());
    }

    public function testAdd()
    {
        $object = new Object('object');
        $field = new Object('field');
        $this->assertSame($object, $object->add($field));
        $parameters = $object->getParameters();
        $this->assertTrue(isset($parameters['properties']['field']));
        $this->assertSame($field, $parameters['properties']['field']);
    }

    /**
     * Teste add() avec un nom de champ qui existe déjà.
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage A field named 'field' already exists
     */
    public function testAddDuplicate()
    {
        $object = new Object('object');
        $field = new Object('field');
        $object->add($field);
        $object->add($field);
    }

    /**
     * Provider pour la méthode testFactories().
     *
     * @return string[][]
     */
    public function factories()
    {
        // méthode => classe
        return [
            ['binary',   Binary::class],
            ['boolean',  Boolean::class],
            ['date',     Date::class],
            ['decimal',  Decimal::class],
            ['geopoint', Geopoint::class],
            ['geoshape', Geoshape::class],
            ['integer',  Integer::class],
            ['ip',       IP::class],
            ['keyword',  Keyword::class],
            ['nested',   Nested::class],
            ['object',   Object::class],
            ['text',     Text::class],
        ];
    }

    /**
     * Teste toutes les méthodes de la classe Object qui permettent de créer un champ.
     *
     * @param string $method Nom de la méthode de factory à tester.
     * @param string $class  Nom complet de la classe Field que doit retourner la méthode.
     *
     * @dataProvider factories
     */
    public function testFactories($method, $class)
    {
        $object = new Object('test');

        // Crée le champ avec ses paramètres par défaut
        $field = $object->$method('field'); /** @var Field $field */

        // Vérifie que la méthode retourne bien un objet du bon type
        $this->assertSame($class, get_class($field));

        // Vérifie que le champ a le bon nom
        $this->assertSame('field', $field->getName());

        // Vérifie que le champ a été initialisé avec les paramètres par défaut
        $this->assertSame($field->getDefaultParameters(), $field->getParameters());

        // Vérifie que le champ a été ajouté à l'objet
        $parameters = $object->getParameters();
        $this->assertTrue(isset($parameters['properties']['field']));
        $this->assertSame($field, $parameters['properties']['field']);

        // Si on passe des paramètres, vérifie qu'ils sont fusionnés avec les paramètres par défaut
        // Remarque : on peut modifier tous les paramètres, y compris le type de champ
        $parameters = ['type' => 'thing', 'my-param' => 'z'];
        $field = $object->$method('field2', $parameters); /** @var Field $field */
        $expected = array_merge($field->getDefaultParameters(), $parameters);
        $this->assertSame($expected, $field->getParameters());
    }
}
