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
 * Gère le paramètre "fielddata" d'un champ de mapping de type "text".
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/fielddata.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
interface FieldData
{
    /**
     * Active les "fielddata" (false par défaut).
     *
     * @return self
     */
    public function enableFieldData(); // pas de return type en attendant covariant-returns

    /**
     * Désactive les "fielddata".
     *
     * @return self
     */
    public function disableFieldData(); // pas de return type en attendant covariant-returns

    /**
     * Teste si les "fielddata" sont activées (false par défaut).
     *
     * @return bool
     */
    public function hasFieldData(): bool;
}
