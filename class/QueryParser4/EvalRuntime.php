<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2011-2016 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search\QueryParser4;

class EvalRuntime extends Runtime
{
    public function emptyInput()
    {
        return null;
    }

    public function doNumber($number)
    {
        return $number;
    }

    public function doAdd($left, $right)
    {
        return $left + $right;
    }

    public function doNegate($number)
    {
        return -$number;
    }

    public function doSub($left, $right)
    {
        return $left - $right;
    }

    public function doMul($left, $right)
    {
        return $left * $right;
    }

    public function doDiv($left, $right)
    {
        return $left / $right;
    }
}
