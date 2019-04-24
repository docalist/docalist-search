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

namespace Docalist\Search\Analysis\TokenFilter\Spanish;

use Docalist\Search\Analysis\TokenFilter;

/**
 * TokenFilter "spanish_stop" : supprime les mots vides espagnols.
 *
 * @link https://github.com/apache/lucene-solr/blob/master/lucene/analysis/common/src/resources/org/apache/
 * lucene/analysis/snowball/spanish_stop.txt
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class SpanishStop implements TokenFilter
{
    /**
     * {@inheritDoc}
     */
    public static function getName(): string
    {
        return 'spanish_stop';
    }

    /**
     * {@inheritDoc}
     */
    public function getDefinition(): array
    {
        return [
            'type' => 'stop',
            'stopwords' => ['_spanish_'],
        ];
    }
}
