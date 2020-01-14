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

use Docalist\Search\Mapping\Field\NumericField;

/**
 * Un entier signé sur 16 bits compris entre -32768 et 32767.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/number.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class ShortField extends NumericField
{
    /**
     * {@inheritDoc}
     */
    final protected function getNumericType(): string
    {
        return 'short';
    }
}
