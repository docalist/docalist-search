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
 * TokenFilter "english_stem_minimal" : stemmer anglais minimal (Donna Harman).
 *
 * Encore moins aggressif que "english_stem_light".
 *
 * @link http://www.researchgate.net/publication/220433848_How_effective_is_suffixing
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class EnglishStemMinimal implements TokenFilter
{
    /**
     * {@inheritDoc}
     */
    public static function getName(): string
    {
        return 'english_stem_minimal';
    }

    /**
     * {@inheritDoc}
     */
    public function getDefinition(): array
    {
        return [
            'type' => 'stemmer',
            'name' => 'minimal_english',
        ];
    }
}
