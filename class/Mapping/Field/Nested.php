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

/**
 * Un champ de type "objets imbriqués".
 *
 * Dans elasticsearch, les données d'un champ 'object' sont linéarisées (cf. doc) et lorsque le champ est multivalué,
 * les propriétés des différents objets sont fusionnées.
 *
 * Le type 'nested' est une version sépcialisée du type 'object' de base qui stocke les différents objets de façon
 * indépendante : chaque objet imbriqué est stocké sous la forme d'un document elasticsearch distinct.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/nested.html
 */
class Nested extends Object
{
    public function getDefaultParameters()
    {
        return [
            'type' => 'nested',
            'properties' => [],
        ];
    }
}
