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

class QueryBuilder extends Runtime
{
    protected $field = '_all';

//     public function emptyInput()
//     {
//         return null;
//     }

    public function getField()
    {
        return $this->field;
    }

    public function setField($field)
    {
        echo "setField : ", $field, '<br />';

        $this->field = $field;

        return $this;
    }

    public function terms(array $terms)
    {
        echo "terme : ", implode(' ', $terms), '<br />';

        return $this->field . ':' . implode(' ', $terms);
//        return "terms(" . implode('¤', $terms) . ")";
    }

    public function phrase($phrase)
    {
        echo "phrase : ", $phrase, '<br />';

        return $this->field . ':' . '"' . $phrase . '"';
    }

    public function and($left, $right)
    {
        return "($left AND $right)";
    }

    public function or($left, $right)
    {
        return "($left OR $right)";
    }

    public function not($left, $right)
    {
        return "($left NOT $right)";
    }

    public function pureNot($expression)
    {
        return "PURE_NOT $expression";
    }

    public function must($expression)
    {
        return '+(' . $expression . ')';
    }

    public function mustNot($expression)
    {
        return '-(' . $expression . ')';
    }

}
