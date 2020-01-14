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

namespace Docalist\Search\Mapping\Field\Info;

use Docalist\Search\Mapping\Field\Info\Features;
use InvalidArgumentException;

/**
 * Implémentation de l'interface Features.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
trait FeaturesTrait // implements Features (https://wiki.php.net/rfc/traits-with-interfaces)
{
    /**
     * Un bitmask indiquant les caractéristiques du champ.
     *
     * @var int
     */
    private $features = 0;

    /**
     * {@inheritDoc}
     */
    public function getSupportedFeatures(): int
    {
        return 0;
    }

    /**
     * {@inheritDoc}
     */
    final public function setFeatures(int $features): self
    {
        if (($this->getSupportedFeatures() & $features) !== $features) {
            throw new InvalidArgumentException(sprintf('Unsupported feature(s) in %s', get_class($this)));
        }

        $this->features = $features;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    final public function getFeatures(): int
    {
        return $this->features;
    }

    /**
     * {@inheritDoc}
     */
    final public function hasFeature(int $feature): bool
    {
        return ($this->features & $feature) === $feature;
    }

    /**
     * Fusionne les caractéristiques.
     *
     * @param Features $other
     */
    final protected function mergeFeatures(Features $other): void
    {
        $this->features |= $other->getFeatures();
    }
}
