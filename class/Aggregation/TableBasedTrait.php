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
 * Un "trait" utilisé par les agrégations qui utilisent une table d'autorité docalist.
 */
trait TableBasedTrait
{
    /**
     * Nom de la table d'autorité.
     *
     * @var string
     */
    protected $tableName;

    /**
     * Objet table d'autorité, initialisée lors du premier appel à getTable().
     *
     * @var TableInterface
     */
    protected $table;

    /**
     * Définit le nom de la table d'autorité utilisée.
     *
     * @param string $table
     *
     * @return self
     */
    public function setTableName($table)
    {
        $this->tableName = $table;

        return $this;
    }

    /**
     * Retourne le nom de la table d'autorité utilisée.
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * Retourne la table d'autorité utilisée.
     *
     * @return TableInterface
     */
    public function getTable()
    {
        is_null($this->table) && $this->table = docalist('table-manager')->get($this->getTableName());

        return $this->table;
    }
}
