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
 * TokenFilter "english_stem_porter2" : version snowball de l'algorithme original de Martin Porter.
 *
 * @link http://snowball.tartarus.org/algorithms/english/stemmer.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class EnglishStemPorter2 implements TokenFilter
{
    /**
     * {@inheritDoc}
     */
    public static function getName(): string
    {
        return 'english_stem_porter2';
    }

    /**
     * {@inheritDoc}
     */
    public function getDefinition(): array
    {
        return [
            'type' => 'stemmer',
            'name' => 'porter2',
        ];
    }
}
