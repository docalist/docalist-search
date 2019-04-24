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
 * TokenFilter "german_stem2" : une variation du stemmer "german_stem" qui tient compte des lettres
 * "ä", "ö" et "ü" (snowball, Martin Porter)
 *
 * @link http://snowball.tartarus.org/algorithms/german2/stemmer.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class GermanStem2 implements TokenFilter
{
    /**
     * {@inheritDoc}
     */
    final public static function getName(): string
    {
        return 'german_stem2';
    }

    /**
     * {@inheritDoc}
     */
    final public function getDefinition(): array
    {
        return [
            'type' => 'stemmer',
            'name' => 'german2',
        ];
    }
}
