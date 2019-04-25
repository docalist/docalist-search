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

use Docalist\Search\Mapping\Field\Parameter\FieldData;
use InvalidArgumentException;

/**
 * Implémentation de l'interface FieldData.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
trait FieldDataTrait
{
    /**
     * Valeur du paramètre (à false par défaut pour les champs de type "text").
     *
     * @var bool
     */
    private $fieldData = false;

    /**
     * {@inheritDoc}
     */
    final public function enableFieldData(): self
    {
        $this->fieldData = true;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    final public function disableFieldData(): self
    {
        $this->fieldData = false;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    final public function hasFieldData(): bool
    {
        return $this->fieldData;
    }

    /**
     * Fusionne avec un autre FieldData.
     *
     * @param FieldData $other
     */
    final protected function mergeFieldData(FieldData $other): void
    {
        if ($other->hasFieldData() !== $this->fieldData) {
            throw new InvalidArgumentException(sprintf(
                'fielddata mismatch (%s vs %s)',
                var_export($other->hasFieldData(), true),
                var_export($this->fieldData, true)
            ));
        }
    }

    /**
     * Applique le paramètre au mapping.
     *
     * @param array $mapping Mapping à modifier.
     */
    final protected function applyFieldData(array & $mapping): void
    {
        $this->fieldData && $mapping['fielddata'] = true;
    }
}
