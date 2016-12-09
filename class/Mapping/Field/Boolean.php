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
 * Un champ booléen qui accepte les valeurs true ou false.
 *
 * Si la valeur du champ est une chaine ou un nombre, celle-ci est convertie en booléen :
 * - 'false', 'off', 'no', '0', '', 0 ou 0.0 : false
 * - tout ce qui n'est pas false : true
 *
 * En interne, les valeurs sont représentées par 1 ou 0. Ce sont ces valeurs qui sont retournées par exemple pour
 * une aggrégation de type 'terms' et ce sont les chaines 'true' et 'false' qui sont utilisées pour key_as_string.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/boolean.html
 */
class Boolean extends Field
{
    public function getDefaultParameters()
    {
        return ['type' => 'boolean'];
    }
}
