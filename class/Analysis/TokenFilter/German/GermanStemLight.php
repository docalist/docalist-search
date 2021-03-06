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

namespace Docalist\Search\Analysis\TokenFilter\German;

use Docalist\Search\Analysis\TokenFilter;

/**
 * TokenFilter "german_stem_light" : stemmer allemand light (Jacques Savoy).
 *
 * Moins aggressif que "german_stem".
 *
 * @link http://dl.acm.org/citation.cfm?id=1141523
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class GermanStemLight implements TokenFilter
{
    /**
     * {@inheritDoc}
     */
    final public static function getName(): string
    {
        return 'german_stem_light';
    }

    /**
     * {@inheritDoc}
     */
    final public function getDefinition(): array
    {
        return [
            'type' => 'stemmer',
            'name' => 'light_german',
        ];
    }
}
