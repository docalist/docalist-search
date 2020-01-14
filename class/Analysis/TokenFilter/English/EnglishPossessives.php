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
 * TokenFilter "english_possessives" : supprime les "'s" à la fin des mots.
 *
 * @link https://github.com/apache/lucene-solr/blob/master/lucene/analysis/common/src/java/org/apache/lucene/
 * analysis/en/EnglishPossessiveFilter.java
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class EnglishPossessives implements TokenFilter
{
    /**
     * {@inheritDoc}
     */
    final public static function getName(): string
    {
        return 'english_possessives';
    }

    /**
     * {@inheritDoc}
     */
    final public function getDefinition(): array
    {
        return [
            'type' => 'stemmer',
            'language' => 'possessive_english',
        ];
    }
}
