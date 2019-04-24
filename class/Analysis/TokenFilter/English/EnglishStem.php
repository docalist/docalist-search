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

namespace Docalist\Search\Analysis\TokenFilter\English;

use Docalist\Search\Analysis\TokenFilter;

/**
 * TokenFilter "english_stem" : stemmer anglais standard (Martin Porter, algorithme original).
 *
 * @link http://snowball.tartarus.org/algorithms/porter/stemmer.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class EnglishStem implements TokenFilter
{
    /**
     * {@inheritDoc}
     */
    public static function getName(): string
    {
        return 'english_stem';
    }

    /**
     * {@inheritDoc}
     */
    public function getDefinition(): array
    {
        return [
            'type' => 'stemmer',
            'name' => 'english',
        ];
    }
}
