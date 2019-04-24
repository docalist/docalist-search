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

namespace Docalist\Search\Analysis\TokenFilter\German;

use Docalist\Search\Analysis\TokenFilter;

/**
 * TokenFilter "german_stop" : supprime les mots vides allemands.
 *
 * @link https://github.com/apache/lucene-solr/blob/master/lucene/analysis/common/src/resources/org/apache/
 * lucene/analysis/snowball/german_stop.txt
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class GermanStop implements TokenFilter
{
    /**
     * {@inheritDoc}
     */
    public static function getName(): string
    {
        return 'german_stop';
    }

    /**
     * {@inheritDoc}
     */
    public function getDefinition(): array
    {
        return [
            'type' => 'stop',
            'stopwords' => ['_german_'],
        ];
    }
}
