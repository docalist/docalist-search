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
namespace Docalist\Search\Tests\Mapping;

use WP_UnitTestCase;
use Docalist\Search\Mapping\Field;

class FieldTest extends WP_UnitTestCase
{
    public function testGetName()
    {
        $field = $this->getMockForAbstractClass(Field::class, ['field']); /** @var Field $field */
        $this->assertSame('field', $field->getName());
        $this->assertSame([], $field->getDefaultParameters(), $field);
    }

    public function testGetParameters()
    {
        $field = $this->getMockForAbstractClass(Field::class, ['field']); /** @var Field $field */
        $this->assertSame($field->getDefaultParameters(), $field->getParameters());

        $params = ['type' => 'text', 'analyzer' => 'text'];
        $field = $this->getMockForAbstractClass(Field::class, ['field', $params]); /** @var Field $field */
        $this->assertSame($params, $field->getParameters());
    }
}
