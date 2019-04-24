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

namespace Docalist\Search\Analysis;

use Docalist\Search\Analysis\Analyzer\EnglishText;
use Docalist\Search\Analysis\Analyzer\FrenchText;
use Docalist\Search\Analysis\Analyzer\GermanText;
use Docalist\Search\Analysis\Analyzer\Hierarchy;
use Docalist\Search\Analysis\Analyzer\ItalianText;
use Docalist\Search\Analysis\Analyzer\SpanishText;
use Docalist\Search\Analysis\Analyzer\Suggest;
use Docalist\Search\Analysis\Analyzer\Text;
use Docalist\Search\Analysis\Analyzer\Url;

use Docalist\Search\Analysis\CharFilter\UrlNormalizeSep;
use Docalist\Search\Analysis\CharFilter\UrlRemovePrefix;
use Docalist\Search\Analysis\CharFilter\UrlRemoveProtocol;

use Docalist\Search\Analysis\TokenFilter\English\EnglishPossessives;
use Docalist\Search\Analysis\TokenFilter\English\EnglishStem;
use Docalist\Search\Analysis\TokenFilter\English\EnglishStemLight;
use Docalist\Search\Analysis\TokenFilter\English\EnglishStemMinimal;
use Docalist\Search\Analysis\TokenFilter\English\EnglishStemPorter2;
use Docalist\Search\Analysis\TokenFilter\English\EnglishStop;

use Docalist\Search\Analysis\TokenFilter\French\FrenchElision;
use Docalist\Search\Analysis\TokenFilter\French\FrenchStem;
use Docalist\Search\Analysis\TokenFilter\French\FrenchStemLight;
use Docalist\Search\Analysis\TokenFilter\French\FrenchStemMinimal;
use Docalist\Search\Analysis\TokenFilter\French\FrenchStop;

use Docalist\Search\Analysis\TokenFilter\German\GermanStem;
use Docalist\Search\Analysis\TokenFilter\German\GermanStem2;
use Docalist\Search\Analysis\TokenFilter\German\GermanStemLight;
use Docalist\Search\Analysis\TokenFilter\German\GermanStemMinimal;
use Docalist\Search\Analysis\TokenFilter\German\GermanStop;

use Docalist\Search\Analysis\TokenFilter\Italian\ItalianElision;
use Docalist\Search\Analysis\TokenFilter\Italian\ItalianStem;
use Docalist\Search\Analysis\TokenFilter\Italian\ItalianStemLight;
use Docalist\Search\Analysis\TokenFilter\Italian\ItalianStop;

use Docalist\Search\Analysis\TokenFilter\Spanish\SpanishStem;
use Docalist\Search\Analysis\TokenFilter\Spanish\SpanishStemLight;
use Docalist\Search\Analysis\TokenFilter\Spanish\SpanishStop;
use Docalist\Search\Analysis\Tokenizer\UrlTokenizer;

/**
 * Components : liste des composants d'analyse prédéfinis de docalist-search.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class Components
{
    /**
     * Retourne la liste des analyseurs pré-définis de docalist-search.
     *
     * @return string[] Un tableau de la forme "nom" => "nom de classe php de type Analyzer".
     */
    final public static function getAnalyzers(): array
    {
        return [
            EnglishText::getName() => EnglishText::class,
            FrenchText::getName() => FrenchText::class,
            GermanText::getName() => GermanText::class,
            Hierarchy::getName() => Hierarchy::class,
            ItalianText::getName() => ItalianText::class,
            SpanishText::getName() => SpanishText::class,
            Suggest::getName() => Suggest::class,
            Text::getName() => Text::class,
            Url::getName() => Url::class,
        ];
    }

    /**
     * Retourne la liste des filtres de caractères pré-définis de docalist-search.
     *
     * @return string[] Un tableau de la forme "nom" => "nom de classe php de type CharFilter".
     */
    final public static function getCharFilters(): array
    {
        return [
            UrlNormalizeSep::getName() => UrlNormalizeSep::class,
            UrlRemovePrefix::getName() => UrlRemovePrefix::class,
            UrlRemoveProtocol::getName() => UrlRemoveProtocol::class,
        ];
    }

    /**
     * Retourne la liste des filtres de tokens pré-définis de docalist-search.
     *
     * @return string[] Un tableau de la forme "nom" => "nom de classe php de type TokenFilter".
     */
    final public static function getTokenFilters(): array
    {
        return [
            EnglishPossessives::getName() => EnglishPossessives::class,
            EnglishStem::getName() => EnglishStem::class,
            EnglishStemLight::getName() => EnglishStemLight::class,
            EnglishStemMinimal::getName() => EnglishStemMinimal::class,
            EnglishStemPorter2::getName() => EnglishStemPorter2::class,
            EnglishStop::getName() => EnglishStop::class,

            FrenchElision::getName() => FrenchElision::class,
            FrenchStem::getName() => FrenchStem::class,
            FrenchStemLight::getName() => FrenchStemLight::class,
            FrenchStemMinimal::getName() => FrenchStemMinimal::class,
            FrenchStop::getName() => FrenchStop::class,

            GermanStem::getName() => GermanStem::class,
            GermanStem2::getName() => GermanStem2::class,
            GermanStemLight::getName() => GermanStemLight::class,
            GermanStemMinimal::getName() => GermanStemMinimal::class,
            GermanStop::getName() => GermanStop::class,

            ItalianElision::getName() => ItalianElision::class,
            ItalianStem::getName() => ItalianStem::class,
            ItalianStemLight::getName() => ItalianStemLight::class,
            ItalianStop::getName() => ItalianStop::class,

            SpanishStem::getName() => SpanishStem::class,
            SpanishStemLight::getName() => SpanishStemLight::class,
            SpanishStop::getName() => SpanishStop::class,
        ];
    }

    /**
     * Retourne la liste des tokenizers pré-définis de docalist-search.
     *
     * @return string[] Un tableau de la forme "nom" => "nom de classe php de type Tokenizer".
     */
    final public static function getTokenizers(): array
    {
        return [
            UrlTokenizer::getName() => UrlTokenizer::class,
        ];
    }
}
