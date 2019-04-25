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

use Docalist\Search\Mapping\Field\Info\Description;

/**
 * Implémentation de l'interface Description.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
trait DescriptionTrait
{
    /**
     * Valeur du paramètre.
     *
     * @var string
     */
    private $description = '';

    /**
     * {@inheritDoc}
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Fusionne avec une autre description.
     *
     * @param Description $other
     */
    protected function mergeDescription(Description $other): void
    {
        $description = $other->getDescription();
        if ($description !== $this->description) {
            $this->description = trim($this->description . ' ' . $description);
        }
    }
}
