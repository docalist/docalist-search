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

use Docalist\Search\Mapping\Field;

/**
 * Gère la description d'un champ de mapping.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
interface Description
{
    /**
     * Modifie la description du champ.
     *
     * @param string $description
     *
     * @return self
     */
    public function setDescription(string $description); // pas de return type en attendant covariant-returns

    /**
     * Retourne la description du champ.
     *
     * @return string
     */
    public function getDescription(): string;
}
