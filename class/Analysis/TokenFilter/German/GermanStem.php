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

namespace Docalist\Search\Analysis\TokenFilter\German;

use Docalist\Search\Analysis\TokenFilter;

/**
 * TokenFilter "german_stem" : stemmer allemand standard (snowball, Martin Porter).
 *
 * @link http://snowball.tartarus.org/algorithms/german/stemmer.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class GermanStem implements TokenFilter
{
    /**
     * {@inheritDoc}
     */
    public static function getName(): string
    {
        return 'german_stem';
    }

    /**
     * {@inheritDoc}
     */
    public function getDefinition(): array
    {
        return [
            'type' => 'stemmer',
            'name' => 'german',
        ];
    }
}
