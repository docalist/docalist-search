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

use Docalist\Search\Mapping\Field\NumericField;

/**
 * Un entier signé sur 8 bits compris entre -128 et 127.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/number.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class ByteField extends NumericField
{
    /**
     * {@inheritDoc}
     */
    final protected function getNumericType(): string
    {
        return 'byte';
    }
}
