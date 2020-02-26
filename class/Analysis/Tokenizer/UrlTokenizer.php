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
        //
        return [
            'type' => 'char_group',
            'tokenize_on_chars' => ['whitespace', 'punctuation', 'symbol'],
            /*
             * cf. https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-chargroup-tokenizer.html
             *
             * On "coupe" à chaque fois qu'on rencontre un espace, un signe de ponctuation ou un symbole.
             * Autrement dit, on conserve les lettres et les chiffres ('letter' + 'digit')
             */
        ];
    }
}
