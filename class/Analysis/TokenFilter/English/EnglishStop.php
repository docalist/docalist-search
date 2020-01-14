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

namespace Docalist\Search\Analysis\TokenFilter\English;

use Docalist\Search\Analysis\TokenFilter;

/**
 * TokenFilter "english_stop" : supprime les mots vides anglais.
 *
 * @link https://github.com/apache/lucene-solr/blob/master/lucene/analysis/common/src/resources/org/apache/
 * lucene/analysis/snowball/english_stop.txt
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class EnglishStop implements TokenFilter
{
    /**
     * {@inheritDoc}
     */
    final public static function getName(): string
    {
        return 'english_stop';
    }

    /**
     * {@inheritDoc}
     */
    final public function getDefinition(): array
    {
        return [
            'type' => 'stop',
            'stopwords' => ['_english_'],
        ];
    }
}
