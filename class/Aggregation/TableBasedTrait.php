<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Search\Aggregation;

use Docalist\Table\TableInterface;

/**
 * Un "trait" utilisé par les agrégations qui utilisent des tables d'autorité docalist.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
trait TableBasedTrait
{
    /**
     * Tables d'autorité.
     *
     * @var TableInterface[] Un tableau de la forme index => objet Table.
     */
    protected $tables;

    /**
     * Définit les tables d'autorité utilisées.
     *
     * @param string|array $tables Un ou plusieurs noms de tables d'autorité.
     *
     * @return self
     */
    public function setTables($tables)
    {
        $this->tables = [];
        foreach ((array) $tables as $table) {
            $this->tables[] = docalist('table-manager')->get($table);
        }

        return $this;
    }

    /**
     * Retourne les tables d'autorité utilisées.
     *
     * @return TableInterface[] Un tableau de tables.
     */
    public function getTables()
    {
        return $this->tables;
    }
}
