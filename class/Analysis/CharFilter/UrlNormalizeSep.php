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

namespace Docalist\Search\Analysis\CharFilter;

use Docalist\Search\Analysis\CharFilter;

/**
 * CharFilter "url_normalize_sep" : normalise les séparateurs dans une url.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class UrlNormalizeSep implements CharFilter
{
    /**
     * {@inheritDoc}
     */
    public static function getName(): string
    {
        return 'url_normalize_sep';
    }

    /**
     * {@inheritDoc}
     */
    public function getDefinition(): array
    {
        return [
            'type' => 'pattern_replace',
            'pattern' => '[/\\\\#@:]+',
            'replacement' => '/',
        ];
    }
}
