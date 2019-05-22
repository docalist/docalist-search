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

use Docalist\Search\Mapping\Field\Info\FeaturesConstants;
use InvalidArgumentException;

/**
 * Les caractéristiques d'un champ de mapping.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
interface Features extends FeaturesConstants
{
    /**
     * Retourne la liste des caractéristiques supportées par le champ.
     *
     * @return int Un bitmask indiquant les caractéristiques supportées.
     */
    public function getSupportedFeatures(): int;

    /**
     * Définit les caractéristiques du champ.
     *
     * @param int $features Un bitmask indiquant les caractéristiques supportées.
     *
     * @throws InvalidArgumentException Si une des caractéristiques indiquées n'est pas supportée.
     *
     * @return self
     */
    public function setFeatures(int $features); // pas de return type en attendant covariant-returns

    /**
     * Retourne les caractéristiques du champ.
     *
     * @return int Un bitmask indiquant les caractéristiques supportées.
     */
    public function getFeatures(): int;

    /**
     * Teste si le champ a les caractéristiques indiquées.
     *
     * @param int $feature Une ou plusieurs caractéristiques à tester.
     *
     * @return bool True si le champ a la caractéristique indiquée (toutes si plusieurs).
     */
    public function hasFeature(int $feature): bool;
}
