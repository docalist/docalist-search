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
 * TokenFilter "english_stem_light" : stemmer anglais light (Robert Krovetz).
 *
 * Moins aggressif que "english_stem".
 *
 * @link http://ciir.cs.umass.edu/pubfiles/ir-35.pdf
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class EnglishStemLight implements TokenFilter
{
    /**
     * {@inheritDoc}
     */
    public static function getName(): string
    {
        return 'english_stem_light';
    }

    /**
     * {@inheritDoc}
     */
    public function getDefinition(): array
    {
        return [
            'type' => 'stemmer',
            'name' => 'light_english',
        ];
    }
}
