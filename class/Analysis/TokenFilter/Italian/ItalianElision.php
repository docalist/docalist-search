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
 * TokenFilter "italian_elision" : supprime les élisions en italien.
 *
 * @link https://github.com/apache/lucene-solr/blob/master/lucene/analysis/common/src/java/org/apache/lucene/
 * analysis/it/ItalianAnalyzer.java#L53
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class ItalianElision implements TokenFilter
{
    /**
     * {@inheritDoc}
     */
    final public static function getName(): string
    {
        return 'italian_elision';
    }

    /**
     * {@inheritDoc}
     */
    final public function getDefinition(): array
    {
        return [
            'type' => 'elision',
            'articles' => [
                'c', 'l', 'all', 'dall', 'dell', 'nell', 'sull', 'coll', 'pell',
                'gl', 'agl', 'dagl', 'degl', 'negl', 'sugl', 'un', 'm', 't', 's', 'v', 'd',
            ],
        ];
    }
}
