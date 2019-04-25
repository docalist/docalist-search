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

/**
 * Un entier signé sur 64 bits compris entre -2^63 et 2^63-1.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/number.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class LongField extends NumericField
{
    /**
     * {@inheritDoc}
     */
    final protected function getNumericType(): string
    {
        return 'long';
    }
}
