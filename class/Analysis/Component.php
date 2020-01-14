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

namespace Docalist\Search\Analysis;

/**
 * Interface d'un composant d'analyse.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/analyzer-anatomy.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
interface Component
{
    /**
     * Retourne le nom du composant.
     *
     * @return string
     */
    public static function getName(): string;

    /**
     * Retourne la définition du composant.
     *
     * @return array
     */
    public function getDefinition(): array;
}
