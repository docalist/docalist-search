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
use Docalist\Search\Mapping\Field\IP;

class IPTest extends WP_UnitTestCase
{
    public function testDefaultParameters()
    {
        $field = new IP('field');
        $this->assertSame([
            'type' => 'ip',
        ], $field->getDefaultParameters());
    }
}
