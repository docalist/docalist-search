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

namespace Docalist\Search\Analysis\Tokenizer;

use Docalist\Search\Analysis\Tokenizer;

/**
 * Tokenizer "url_tokenizer" : découpe des uri/url en tokens.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class UrlTokenizer implements Tokenizer
{
    /**
     * {@inheritDoc}
     */
    final public static function getName(): string
    {
        return 'url_tokenizer';
    }

    /**
     * {@inheritDoc}
     */
    final public function getDefinition(): array
    {
        return [
            'type' => 'keyword',
        ];
    }
}
