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

namespace Docalist\Search\QueryDSL;

/**
 * Méthodes utilitaires pour manipuler le Query DSL Elasticsearch.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
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
        return ['match_none' => (object) []];
    }
}
