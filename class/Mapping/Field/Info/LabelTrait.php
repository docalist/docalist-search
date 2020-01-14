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

use Docalist\Search\Mapping\Field\Info\Label;

/**
 * Implémentation de l'interface Label.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
trait LabelTrait
{
    /**
     * Valeur du paramètre.
     *
     * @var string
     */
    private $label = '';

    /**
     * {@inheritDoc}
     */
    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Fusionne avec un autre Label.
     *
     * @param Label $other
     */
    protected function mergeLabel(Label $other): void
    {
        $label = $other->getLabel();
        if ($label !== $this->label) {
            $this->label = trim($this->label . ' ' . $label);
        }
    }
}
