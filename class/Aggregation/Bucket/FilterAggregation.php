<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2013-2017 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search\Aggregation\Bucket;

use Docalist\Search\Aggregation\SingleBucketAggregation;

/**
 * Une agrégation de type "bucket" qui regroupe tous les documents qui correspondent à un filtre donné.
 *
 * @link
 * https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations-bucket-filter-aggregation.html
 */
class FilterAggregation extends SingleBucketAggregation
{
    const TYPE = 'filter';

    /**
     * Constructeur
     *
     * @param array     $filter         Définition DSL du filtre à appliquer à l'agrégation.
     * @param array     $options        Options d'affichage.
     */
    public function __construct(array $filter, array $options = [])
    {
        parent::__construct($filter, $options);
    }

    public function render(array $options = [])
    {
        /*
         * Une agrégation de type 'filter' ne génère rien elle-même : elle se contente d'afficher
         * les sous-agrégations qu'elle contient. Du coup, elle n'a pas de container, pas de titre, etc.
         * Donc on surcharge la méthode render() héritée de BaseAggregation pour court-circuiter
         * complètement le traitement par défaut.
         * Important : les options d'affichage passées à render() s'appliquent aux sous-agrégations, pas à
         * l'agrégation globale.
         */

        // On ne génère rien si on n'a pas de résultat ou si on n'a aucune sous-agrégation
        if (is_null($this->result) || !$this->hasAggregations()) {
            return '';
        }

        // Prépare le bucket (transmet leur résultat aux sous-agrégations)
        $this->prepareBucket($this->result);

        // Génère chacune des sous-agrégations
        $result = '';
        foreach($this->getAggregations() as $aggregation) {
            $result .= $aggregation->render($options);
        }

        // Ok
        return $result;
    }
}
