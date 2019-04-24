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

namespace Docalist\Search\Analysis\TokenFilter\French;

use Docalist\Search\Analysis\TokenFilter;

/**
 * TokenFilter "french_stop" : supprime les mots vides français.
 *
 * @link https://github.com/apache/lucene-solr/blob/master/lucene/analysis/common/src/resources/org/apache/
 * lucene/analysis/snowball/french_stop.txt
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class FrenchStop implements TokenFilter
{
    /**
     * {@inheritDoc}
     */
    final public static function getName(): string
    {
        return 'french_stop';
    }

    /**
     * {@inheritDoc}
     */
    final public function getDefinition(): array
    {
        return [
            'type' => 'stop',
            'stopwords' => ['_french_'],
        ];
    }
}
