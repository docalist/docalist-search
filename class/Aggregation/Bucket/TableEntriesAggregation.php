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
namespace Docalist\Search\Aggregation\Bucket;

use Docalist\Search\Aggregation\Bucket\TermsAggregation;
use Docalist\Search\Aggregation\TableBasedTrait;

/**
 * Une agrégation de type "terms" qui traduit les termes obtenus en utilisant une table d'autorité docalist.
 */
class TableEntriesAggregation extends TermsAggregation
{
    use TableBasedTrait;

    /**
     * Constructeur
     *
     * @param string    $field      Champ sur lequel porte l'agrégation.
     * @param string    $table      Nom de la table d'autorité utilisée pour convertir les termes en libellés.
     * @param array     $parameters Autres paramètres de l'agrégation.
     */
    public function __construct($field, $table, array $parameters = [])
    {
        parent::__construct($field, $parameters);
        $this->setTableName($table);
    }

    public function getBucketLabel($bucket)
    {
        if ($bucket->key === static::MISSING) {
            return $this->getLabelForMissing();
        }

        $table = $this->getTable();
        $label = $table->find('label', 'code=' . $table->quote($bucket->key));

        return $label === false ? $bucket->key : $label;
    }
}
