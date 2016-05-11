<?php
/**
 * This file is part of the "Docalist Biblio UserData" plugin.
 *
 * Copyright (C) 2015-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Tests\Biblio\UserData
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Tests\Search\QueryDSL;

use WP_UnitTestCase;
use Docalist\Search\QueryDSL\Version500 as DSL;

class DSL500Test extends WP_UnitTestCase
{
    public function testVersion()
    {
        $dsl = new DSL();
        $this->assertSame($dsl->getVersion(), '5.x.x');
    }

    public function testMatchNone()
    {
        $dsl = new DSL();

        $query = $dsl->matchNone();
        $this->assertSame($query, [ 'match_none' => [] ]);
    }
}
