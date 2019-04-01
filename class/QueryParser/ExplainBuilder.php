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

namespace Docalist\Search\QueryParser;

use Docalist\Search\QueryParser\Builder;

/**
 * Builder utilisé par le QueryParser pour expliquer comment la requête a été analysée.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ExplainBuilder implements Builder
{
    public function match($field, array $terms)
    {
        if (count($terms) === 1) {
            return $field . ':' . reset($terms);
        }

        return $field . ':(' . implode(' AND ', $terms) . ')';
    }

    public function phrase($field, array $terms)
    {
        return $field . ':"' . implode(' ', $terms) . '"';
    }

    public function prefix($field, $prefix)
    {
        return $field . ':' . $prefix . '*';
    }

    public function all()
    {
        return '*';
    }

    public function exists($field)
    {
        return "$field:*";
    }

    public function bool(array $should = [], array $must = [], array $not = [])
    {
        $result = '';
        if ($must) {
            $result = '(' . implode(' AND ', $must) . ')';
        }
        if ($should) {
            $result && $result .= ' AND_MAYBE ';
            $result .= '(' . implode(' OR ', $should) . ')';
        }
        if ($not) {
            $result && $result .= ' ';
            $result .= 'NOT ' . implode(' NOT ', $not);
        }

        return $result;
    }

    public function range($field, $start, $end)
    {
        $range = '';
        !is_null($start) && $range = $start;
        $range .= '..';
        !is_null($end) && $range .= $end;

        return $range;
    }
}
