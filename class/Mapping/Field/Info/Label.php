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

use Docalist\Search\Mapping\Field;

/**
 * Gère le libellé d'un champ de mapping.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
interface Label
{
    /**
     * Modifie le libellé du champ.
     *
     * @param string $label
     *
     * @return self
     */
    public function setLabel(string $label); // pas de return type en attendant covariant-returns

    /**
     * Retourne le libellé du champ.
     *
     * @return string
     */
    public function getLabel(): string;
}
