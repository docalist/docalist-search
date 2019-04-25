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
 * Un nombre réel à virgule flottante IEEE-754 sur 16 bits en demi précision.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/number.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class HalfFloatField extends NumericField
{
    /**
     * {@inheritDoc}
     */
    final protected function getNumericType(): string
    {
        return 'half_float';
    }
}
