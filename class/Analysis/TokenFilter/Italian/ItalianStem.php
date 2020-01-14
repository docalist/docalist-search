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

namespace Docalist\Search\Analysis\TokenFilter\Italian;

use Docalist\Search\Analysis\TokenFilter;

/**
 * TokenFilter "italian_stem" : stemmer italien standard (snowball, Martin Porter).
 *
 * @link http://snowball.tartarus.org/algorithms/italian/stemmer.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class ItalianStem implements TokenFilter
{
    /**
     * {@inheritDoc}
     */
    final public static function getName(): string
    {
        return 'italian_stem';
    }

    /**
     * {@inheritDoc}
     */
    final public function getDefinition(): array
    {
        return [
            'type' => 'stemmer',
            'name' => 'italian',
        ];
    }
}
