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

namespace Docalist\Search\Mapping\Field\Parameter;

use Docalist\Search\Mapping\Options;

/**
 * Gère le nom d'un champ de mapping.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
interface Name
{
    /**
     * Retourne le nom du champ.
     *
     * @return string
     */
    public function getName(): string;

    // Remarque : setName() est protected, on ne peut pas changer le nom d'un champ.
    // Elle est implémentée dans le trait mais elle ne figure pas dans l'interface.
}
