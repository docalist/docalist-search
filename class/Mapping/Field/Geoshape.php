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

namespace Docalist\Search\Mapping\Field;

use Docalist\Search\Mapping\Field;
use Docalist\Search\Mapping\Options;

/**
 * Une forme géographique (un rectangle, un polygone, un cercle, une ligne, un point...)
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/geo-shape.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class GeoshapeField extends Field
{
    /**
     * {@inheritDoc}
     */
    final public function getMapping(Options $options): array
    {
        // Génère le mapping de base
        $mapping = parent::getMapping($options);

        // Type de champ
        $mapping['type'] = 'geo_shape';
        $mapping['ignore_malformed'] = true;

        // Ok
        return $mapping;
    }
}
