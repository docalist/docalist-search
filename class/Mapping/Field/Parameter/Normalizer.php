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

use Docalist\Search\Mapping\Options;

/**
 * Gère le paramètre "normalizer" d'un champ de mapping.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/normalizer.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
interface Normalizer
{
    /**
     * Modifie le nom du "normalizer" utilisé par le champ.
     *
     * @param string $normalizer
     *
     * @return self
     */
    public function setNormalizer(string $normalizer); // pas de return type en attendant covariant-returns

    /**
     * Retourne le nom du "normalizer" utilisé par le champ.
     *
     * @return string
     */
    public function getNormalizer(): string;
}
