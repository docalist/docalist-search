<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2012-2016 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search\Mapping\Field;

use Docalist\Search\Mapping\Field;

/**
 * Un champ représentant une aire géographique (rectangle, polygone, etc.)
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/geo-shape.html
 */
class Geoshape extends Field
{
    public function getDefaultParameters()
    {
        return ['type' => 'geo_shape'];
    }
}
