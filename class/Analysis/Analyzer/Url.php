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

namespace Docalist\Search\Analysis\Analyzer;

use Docalist\Search\Analysis\Analyzer\CustomAnalyzer;
use Docalist\Search\Analysis\CharFilter\UrlRemoveProtocol;
use Docalist\Search\Analysis\CharFilter\UrlRemovePrefix;
use Docalist\Search\Analysis\CharFilter\UrlNormalizeSep;
use Docalist\Search\Analysis\Tokenizer\UrlTokenizer;

/**
 * Analyseur "url" : analyseur pour les uris/urls.
 *
 * @see http://stackoverflow.com/a/18980048
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class Url extends CustomAnalyzer
{
    /**
     * {@inheritDoc}
     */
    final public static function getName(): string
    {
        return 'url';
    }

    /**
     * {@inheritDoc}
     */
    final public function getTokenizer(): string
    {
        return UrlTokenizer::getName();
    }

    /**
     * {@inheritDoc}
     */
    final public function getTokenFilters(): array
    {
        return [
            'lowercase',    // Convertit le texte en minuscules
            'asciifolding', // Supprime les accents
        ];
    }
}
