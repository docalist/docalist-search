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
 * CharFilter "url_remove_protocol" : supprime le protocole d'une url.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class UrlRemoveProtocol implements CharFilter
{
    /**
     * {@inheritDoc}
     */
    public static function getName(): string
    {
        return 'url_remove_protocol';
    }

    /**
     * {@inheritDoc}
     */
    public function getDefinition(): array
    {
        return [
            'type' => 'pattern_replace',
            'pattern' => '^[a-zA-Z]+:/*',
            'replacement' => '',
        ];
    }
}
