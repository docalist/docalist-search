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
     * Caractéristiques du champ.
     *
     * @var string[]
     */
    private $features = [];

    /**
     * {@inheritDoc}
     */
    public function getSupportedFeatures(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    final public function setFeatures(array $features): self
    {
        $bad = array_diff($features, $this->getSupportedFeatures());
        if (empty($bad)) {
            $this->features = $features;

            return $this;
        }

        throw new InvalidArgumentException(sprintf(
            'Unsupported feature(s) in %s: %s',
            get_class($this),
            implode(', ', $bad)
        ));
    }

    /**
     * {@inheritDoc}
     */
    final public function getFeatures(): array
    {
        return $this->features;
    }

    /**
     * {@inheritDoc}
     */
    final public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features);
    }

    /**
     * Fusionne les caractéristiques.
     *
     * @param Features $other
     */
    final protected function mergeFeatures(Features $other): void
    {
        $this->features = array_merge($this->features, $other->getFeatures());
    }
}
