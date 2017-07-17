<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2013-2016 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search\Aggregation;

use Docalist\Table\TableInterface;

/**
 * Un "trait" utilisé par les agrégations qui utilisent des tables d'autorité docalist.
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
