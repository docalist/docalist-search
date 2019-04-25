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

use Docalist\Search\Mapping\Field\Parameter\Normalizer;
use InvalidArgumentException;

/**
 * Implémentation de l'interface Normalizer.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
trait NormalizerTrait
{
    /**
     * Valeur du paramètre.
     *
     * @var string
     */
    private $normalizer = '';

    /**
     * {@inheritDoc}
     */
    final public function setNormalizer(string $normalizer): self
    {
        $this->normalizer = $normalizer;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    final public function getNormalizer(): string
    {
        return $this->normalizer;
    }

    /**
     * Fusionne avec un autre Normalizer.
     *
     * @param Normalizer $other
     */
    final protected function mergeNormalizer(Normalizer $other): void
    {
        if ($other->getNormalizer() !== $this->normalizer) {
            throw new InvalidArgumentException(sprintf(
                'normalizer mismatch (%s vs %s)',
                var_export($other->getNormalizer(), true),
                var_export($this->normalizer, true)
            ));
        }
    }

    /**
     * Applique le paramètre au mapping.
     *
     * @param array $mapping Mapping à modifier.
     */
    final protected function applyNormalizer(array & $mapping): void
    {
        !empty($this->normalizer) && $mapping['normalizer'] = $this->normalizer;
    }
}
