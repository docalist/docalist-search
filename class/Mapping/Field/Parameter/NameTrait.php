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

use Docalist\Search\Mapping\Field\Parameter\Name;
use Docalist\Search\Mapping\Options;
use InvalidArgumentException;

/**
 * Implémentation de l'interface Name.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
trait NameTrait
{
    /**
     * Valeur du paramètre.
     *
     * @var string
     */
    private $name = '';

    /**
     * Modifie le nom du champ.
     *
     * @param string $name
     *
     * @return self
     */
    final protected function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    final public function getName(): string
    {
        return $this->name;
    }

    /**
     * Fusionne avec un autre Name.
     *
     * @param Name $other
     */
    protected function mergeName(Name $other): void // pas final, surchargée dans Mapping
    {
        if ($other->getName() !== $this->name) {
            throw new InvalidArgumentException(sprintf(
                'name mismatch (%s vs %s)',
                var_export($other->getName(), true),
                var_export($this->name, true)
            ));
        }
    }
}
