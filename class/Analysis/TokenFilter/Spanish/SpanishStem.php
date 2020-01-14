<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2020 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Search\Analysis\TokenFilter\Spanish;

use Docalist\Search\Analysis\TokenFilter;

/**
 * TokenFilter "spanish_stem" : stemmer espagnol standard (snowball, Martin Porter).
 *
 * @link http://snowball.tartarus.org/algorithms/spanish/stemmer.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class SpanishStem implements TokenFilter
{
    /**
     * {@inheritDoc}
     */
    final public static function getName(): string
    {
        return 'spanish_stem';
    }

    /**
     * {@inheritDoc}
     */
    final public function getDefinition(): array
    {
        return [
            'type' => 'stemmer',
            'name' => 'spanish',
        ];
    }
}
