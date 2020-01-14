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
 * TokenFilter "spanish_stem_light" : stemmer espagnol light (Jacques Savoy).
 *
 * Moins aggressif que "spanish_stem".
 *
 * @link http://clef.isti.cnr.it/2003/WN_web/22.pdf
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class SpanishStemLight implements TokenFilter
{
    /**
     * {@inheritDoc}
     */
    final public static function getName(): string
    {
        return 'spanish_stem_light';
    }

    /**
     * {@inheritDoc}
     */
    final public function getDefinition(): array
    {
        return [
            'type' => 'stemmer',
            'name' => 'light_spanish',
        ];
    }
}
