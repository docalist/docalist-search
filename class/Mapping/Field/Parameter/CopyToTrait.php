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

use Docalist\Search\Mapping\Field\Parameter\CopyTo;

/**
 * Implémentation de l'interface CopyTo.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
trait CopyToTrait
{
    /**
     * Valeur du paramètre.
     *
     * @var array
     */
    private $copyTo = [];

    /**
     * {@inheritDoc}
     */
    final public function copyTo(string $field): self
    {
        !in_array($field, $this->copyTo) && $this->copyTo[] = $field;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    final public function getCopyTo(): array
    {
        return $this->copyTo;
    }

    /**
     * Fusionne avec un autre CopyTo.
     *
     * @param Normalizer $other
     */
    final protected function mergeCopyTo(CopyTo $other): void
    {
        $this->copyTo = array_unique(array_merge($this->copyTo, $other->getCopyTo()));
    }

    /**
     * Applique le paramètre au mapping.
     *
     * @param array $mapping Mapping à modifier.
     */
    final protected function applyCopyTo(array & $mapping): void
    {
        if (!empty($this->copyTo)) {
            $mapping['copy_to'] = count($this->copyTo) === 1 ? reset($this->copyTo) : $this->copyTo;
        }
    }
}
