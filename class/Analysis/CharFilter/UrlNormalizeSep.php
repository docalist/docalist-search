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

namespace Docalist\Search\Analysis\CharFilter;

use Docalist\Search\Analysis\CharFilter;

/**
 * CharFilter "url_normalize_sep" : normalise les séparateurs dans une url.
 *
 * @deprecated N'est plus utilisé, conservé au cas où pour servir de modèle.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class UrlNormalizeSep implements CharFilter
{
    /**
     * {@inheritDoc}
     */
    final public static function getName(): string
    {
        return 'url_normalize_sep';
    }

    /**
     * {@inheritDoc}
     */
    final public function getDefinition(): array
    {
        return [
            'type' => 'pattern_replace',
            'pattern' => '[/\\\\#@:]+',
            'replacement' => '/',
        ];
    }
}
