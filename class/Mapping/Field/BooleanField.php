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

namespace Docalist\Search\Mapping\Field;

use Docalist\Search\Mapping\Field;
use Docalist\Search\Mapping\Options;

/**
 * Un champ booléen qui accepte les valeurs true ou false.
 *
 * Le champ reconnaît également les chaines "true" et "false" et les convertit en booléens.
 *
 * En interne, les valeurs sont représentées par 1 ou 0. Ce sont ces valeurs qui sont retournées par exemple pour
 * une aggrégation de type 'terms' et ce sont les chaines 'true' et 'false' qui sont utilisées pour key_as_string.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/boolean.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class BooleanField extends Field
{
    /**
     * {@inheritDoc}
     */
    final public function getMapping(Options $options): array
    {
        // Génère le mapping de base
        $mapping = parent::getMapping($options);

        // Type de champ
        $mapping['type'] = 'boolean';

        // Ok
        return $mapping;
    }
}
