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
use Docalist\Search\Mapping\Field\NumericField;
use Docalist\Search\Mapping\Field\Parameter\ScalingFactor;
use Docalist\Search\Mapping\Field\Parameter\ScalingFactorTrait;
use Docalist\Search\Mapping\Options;
use InvalidArgumentException;

/**
 * Un nombre réel stocké sous forme d'entier long et associé à un facteur d'échelle.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/number.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class ScaledFloatField extends NumericField implements ScalingFactor
{
    use ScalingFactorTrait;

    /**
     * {@inheritDoc}
     */
    final protected function getNumericType(): string
    {
        return 'scaled_float';
    }

    /**
     * {@inheritDoc}
     */
    final public function mergeWith(Field $other): void
    {
        try {
            parent::mergeWith($other);
            $this->mergeScalingFactor($other);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException($other->getName() . ': ' . $e->getMessage());
        }
    }

    /**
     * {@inheritDoc}
     */
    final public function getMapping(Options $options): array
    {
        // Génère le mapping de base
        $mapping = parent::getMapping($options);

        // Applique les autres paramètres
        $this->applyScalingFactor($mapping);

        // Ok
        return $mapping;
    }
}
