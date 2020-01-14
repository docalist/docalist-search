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
 * Pour un champ "keyword", gère la longueur maximale des valeurs qui sont indexées.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/ignore-above.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
interface IgnoreAbove
{
    /**
     * Valeur par défaut pour le paramètre "ignore_above".
     *
     * @var int
     */
    public const DEFAULT_IGNORE_ABOVE = 256;

    /**
     * Modifie la longueur maximale des valeurs d'un champ "keyword" (256 par défaut).
     *
     * Les mots-clés qui ont une longueur supérieure ne sont pas indexés, ils sont ignorés.
     *
     * @param int $ignoreAbove Longueur maximale (minimum 1).
     *
     * @return self
     */
    public function setIgnoreAbove(int $ignoreAbove); // pas de return type en attendant covariant-returns

    /**
     * Retourne la longueur maximale des valeurs d'un champ "keyword" (256 par défaut).
     *
     * Les mots-clés qui ont une longueur supérieure ne sont pas indexés, ils sont ignorés.
     *
     * @return int
     */
    public function getIgnoreAbove(): int;
}
