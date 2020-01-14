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
 * Gère le paramètre "scaling_factor" (facteur d'échelle) d'un champ de mapping de type "scaled_float".
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/number.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
interface ScalingFactor
{
    /**
     * Valeur par défaut pour le paramètre "scaling_factor".
     *
     * @var int
     */
    public const DEFAULT_SCALING_FACTOR = 100;

    /**
     * Modifie le facteur d'échelle d'un champ "scaled_float" (100 par défaut).
     *
     * @param int $scalingFactor Facteur d'échelle (entier supérieur à 1).
     *
     * @return self
     */
    public function setScalingFactor(int $scalingFactor); // pas de return type en attendant covariant-returns

    /**
     * Retourne le facteur d'échelle d'un champ "scaled_float" (100 par défaut).
     *
     * @return int
     */
    public function getScalingFactor(): int;
}
