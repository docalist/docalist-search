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

use Docalist\Search\Mapping\Field\Parameter\IgnoreAbove;
use Docalist\Search\Mapping\Options;
use InvalidArgumentException;
use Docalist\Search\Mapping\Field;

/**
 * Implémentation de l'interface IgnoreAbove.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
trait IgnoreAboveTrait
{
    /**
     * Valeur du paramètre.
     *
     * @var int
     */
    private $ignoreAbove = IgnoreAbove::DEFAULT_IGNORE_ABOVE;

    /**
     * {@inheritDoc}
     */
    final public function setIgnoreAbove(int $ignoreAbove): self
    {
        if ($ignoreAbove < 1) {
            throw new InvalidArgumentException('ignore_above must be > 0');
        }

        $this->ignoreAbove = $ignoreAbove;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    final public function getIgnoreAbove(): int
    {
        return $this->ignoreAbove;
    }

    /**
     * Fusionne avec un autre IgnoreAbove.
     *
     * @param IgnoreAbove $other
     */
    final protected function mergeIgnoreAbove(IgnoreAbove $other): void
    {
        if ($other->getIgnoreAbove() !== $this->ignoreAbove) {
            throw new InvalidArgumentException(sprintf(
                'ignore_above mismatch (%s vs %s)',
                var_export($other->getIgnoreAbove(), true),
                var_export($this->ignoreAbove, true)
            ));
        }
    }

    /**
     * Applique le paramètre au mapping.
     *
     * @param array     $mapping    Mapping à modifier.
     * @param Options   $options    Options du mapping.
     */
    final protected function applyIgnoreAbove(array & $mapping, Options $options): void
    {
        !empty($this->ignoreAbove) && $mapping['ignore_above'] = $this->ignoreAbove;
    }
}
