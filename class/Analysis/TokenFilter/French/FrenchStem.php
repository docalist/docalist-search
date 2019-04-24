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

namespace Docalist\Search\Analysis\TokenFilter\French;

use Docalist\Search\Analysis\TokenFilter;

/**
 * TokenFilter "french_stem" : stemmer français standard (snowball, Martin Porter).
 *
 * @link http://snowball.tartarus.org/algorithms/french/stemmer.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class FrenchStem implements TokenFilter
{
    /**
     * {@inheritDoc}
     */
    final public static function getName(): string
    {
        return 'french_stem';
    }

    /**
     * {@inheritDoc}
     */
    final public function getDefinition(): array
    {
        return [
            'type' => 'stemmer',
            'name' => 'french',
        ];
    }
}
