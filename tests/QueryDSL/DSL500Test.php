<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Tests\Search\QueryDSL;

use WP_UnitTestCase;
use Docalist\Search\QueryDSL\Version500 as DSL;

/**
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
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
