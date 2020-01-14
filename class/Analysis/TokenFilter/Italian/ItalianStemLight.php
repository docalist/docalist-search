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
 * TokenFilter "italian_stem_light" : stemmer italien light (Jacques Savoy).
 *
 * Moins aggressif que "italian_stem".
 *
 * @link http://www.ercim.eu/publication/ws-proceedings/CLEF2/savoy.pdf
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class ItalianStemLight implements TokenFilter
{
    /**
     * {@inheritDoc}
     */
    final public static function getName(): string
    {
        return 'italian_stem_light';
    }

    /**
     * {@inheritDoc}
     */
    final public function getDefinition(): array
    {
        return [
            'type' => 'stemmer',
            'name' => 'light_italian',
        ];
    }
}
