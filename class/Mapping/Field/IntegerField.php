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

namespace Docalist\Search\Mapping\Field;

use Docalist\Search\Mapping\Field;
use Docalist\Search\Mapping\Options;

/**
 * Un entier signé sur 32 bits compris entre -2^31 et 2^31-1.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/number.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class IntegerField extends Field
{
    /**
     * {@inheritDoc}
     */
    final public function getMapping(Options $options): array
    {
        // Génère le mapping de base
        $mapping = parent::getMapping($options);

        // Type de champ
        $mapping['type'] = 'integer';
        $mapping['ignore_malformed'] = true;

        // Ok
        return $mapping;
    }
}
