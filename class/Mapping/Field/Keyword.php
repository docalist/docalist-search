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
 * Un champ de type "mot-clé" utilisé pour du texte non analysé.
 *
 * La valeur fournie au champ génère un terme unique, contrairement au type 'text' qui découpe le texte fourni en
 * plusieurs termes.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/keyword.html
 */
class Keyword extends Field
{
    public function getDefaultParameters()
    {
        return ['type' => 'keyword'];
    }
}
