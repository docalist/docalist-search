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

namespace Docalist\Search\Mapping\Field\Parameter;

use Docalist\Search\Mapping\Field\Parameter\ScalingFactor;
use InvalidArgumentException;

/**
 * Implémentation de l'interface ScalingFactor.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
trait ScalingFactorTrait
{
    /**
     * Valeur du paramètre.
     *
     * @var int
     */
    private $scalingFactor = ScalingFactor::DEFAULT_SCALING_FACTOR;

    /**
     * {@inheritDoc}
     */
    final public function setScalingFactor(int $scalingFactor): self
    {
        if ($scalingFactor < 2) {
            throw new InvalidArgumentException('scaling_factor must be > 1');
        }

        $this->scalingFactor = $scalingFactor;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    final public function getScalingFactor(): int
    {
        return $this->scalingFactor;
    }

    /**
     * Fusionne avec un autre ScalingFactor.
     *
     * @param ScalingFactor $other
     */
    final protected function mergeScalingFactor(ScalingFactor $other): void
    {
        if ($other->getScalingFactor() !== $this->scalingFactor) {
            throw new InvalidArgumentException(sprintf(
                'scaling_factor mismatch (%s vs %s)',
                var_export($other->getScalingFactor(), true),
                var_export($this->scalingFactor, true)
            ));
        }
    }

    /**
     * Applique le paramètre au mapping.
     *
     * @param array $mapping Mapping à modifier.
     */
    final protected function applyScalingFactor(array & $mapping): void
    {
        $mapping['scaling_factor'] = $this->scalingFactor;
    }
}
