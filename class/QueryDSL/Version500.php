<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2013-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search\QueryDSL;

/**
 * Méthodes utilitaires pour manipuler le Query DSL Elasticsearch.
 */
class Version500 extends Version200
{
    public function getVersion()
    {
        return '5.x.x';
    }

    // Dispo en natif depuis ES >= 5.0
    public function matchNone(array $parameters = [])
    {
        return ['match_none' => []];
    }
}
