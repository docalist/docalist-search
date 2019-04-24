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
 * TokenFilter "french_stem_minimal" : stemmer français minimal (Jacques Savoy).
 *
 * Encore moins aggressif que "french_stem_light".
 *
 * @link http://members.unine.ch/jacques.savoy/papers/frjasis.pdf
 * @link http://members.unine.ch/jacques.savoy/clef/frenchStemmer.txt
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class FrenchStemMinimal implements TokenFilter
{
    /**
     * {@inheritDoc}
     */
    public static function getName(): string
    {
        return 'french_stem_minimal';
    }

    /**
     * {@inheritDoc}
     */
    public function getDefinition(): array
    {
        return [
            'type' => 'stemmer',
            'name' => 'minimal_french',
        ];
    }
}
