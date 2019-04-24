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
 * TokenFilter "french_stem_light" : stemmer français light (Jacques Savoy).
 *
 * Moins aggressif que "french_stem".
 *
 * @link https://doc.rero.ch/record/13225/files/Savoy_Jacques_-_Light_Stemming_Approaches_fo_the_French_20091216.pdf
 * @link http://members.unine.ch/jacques.savoy/clef/frenchStemmerPlus.txt
 * @link org.apache.lucene.analysis.fr.FrenchLightStemmer.java
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class FrenchStemLight implements TokenFilter
{
    /**
     * {@inheritDoc}
     */
    final public static function getName(): string
    {
        return 'french_stem_light';
    }

    /**
     * {@inheritDoc}
     */
    final public function getDefinition(): array
    {
        return [
            'type' => 'stemmer',
            'name' => 'light_french',
        ];
    }
}
