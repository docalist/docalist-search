<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Search\Aggregation\Bucket;

use Docalist\Search\Aggregation\TableBasedTrait;

/**
 * Une agrégation de type "range" qui utilise une table d'autorité pour définir les intervalles.
 *
 * La table d'autorité doit contenir les champs suivants : label, start, end
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class TableRangeAggregation extends RangeAggregation
{
    use TableBasedTrait;

    /**
     * Constructeur
     *
     * @param string $field         Champ sur lequel porte l'agrégation.
     * @param string $table         Nom de la table d'autorité utilisée pour convertir les termes en libellés.
     * @param array  $parameters    Autres paramètres de l'agrégation.
     * @param array  $options       Options d'affichage.
     */
    public function __construct($field, $table, array $parameters = [], array $options = [])
    {
        $this->setTables($table); // important : avant l'appel à getRanges()
        parent::__construct($field, $this->getRanges(), $parameters, $options);
    }

    /**
     * Construit les intervalles à partir des entrées qui figurent dans la table d'autorité.
     *
     * @return array
     */
    protected function getRanges()
    {
        // On n'utilise que la première table
        $table = current($this->getTables());

        // La table contient les champs label/start/end mais ES veut que les ranges soient sous la forme key/from/to
        // Pour éviter de faire une boucle pour convertir les noms de champs, on renomme les champs à la volée
        // Au final, on obtient un tableau de stdClass (au lieu d'un tableau de tableaux) mais une fois sérialisé
        // en JSON, ça donne la même chose (à condition que les clés commencent à zéro, d'où le array_values).
        // Remarque : si start ou end est vide, ça génère la valeur 'null', mais ça ne gène pas ES.
        return array_values($table->search('ROWID,label as key,start as `from`,end as `to`'));
    }
}
