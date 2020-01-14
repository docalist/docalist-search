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

namespace Docalist\Search\Analysis\TokenFilter\French;

use Docalist\Search\Analysis\TokenFilter;

/**
 * TokenFilter "french_elision" : supprime les élisions en français.
 *
 * @link https://github.com/apache/lucene-solr/blob/master/lucene/analysis/common/src/java/org/apache/lucene/
 * analysis/fr/FrenchAnalyzer.java#L63
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class FrenchElision implements TokenFilter
{
    /**
     * {@inheritDoc}
     */
    final public static function getName(): string
    {
        return 'french_elision';
    }

    /**
     * {@inheritDoc}
     */
    final public function getDefinition(): array
    {
        return [
            'type' => 'elision',
            'articles' => [
                'l', 'm', 't', 'qu', 'n', 's','j', 'd', 'c', 'jusqu', 'quoiqu','lorsqu', 'puisqu',
            ],
        ];
    }
}
