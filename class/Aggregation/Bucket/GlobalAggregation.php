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

namespace Docalist\Search\Aggregation\Bucket;

use Docalist\Search\Aggregation\SingleBucketAggregation;

/**
 * Une agrégation de type "bucket" qui regroupe tous les documents sans tenir compte de la recherche en cours.
 *
 * @link
 * https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations-bucket-global-aggregation.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class GlobalAggregation extends SingleBucketAggregation
{
    const TYPE = 'global';

    /**
     * {@inheritDoc}
     */
    public function render(array $options = []): string
    {
        /*
         * Une agrégation de type 'global' ne génère rien elle-même : elle se contente d'afficher
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
        foreach ($this->getAggregations() as $aggregation) {
            $result .= $aggregation->render($options);
        }

        // Ok
        return $result;
    }
}
