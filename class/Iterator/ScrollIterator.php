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

namespace Docalist\Search\Iterator;

use Docalist\Search\SearchRequest;
use IteratorAggregate;
use Generator;

/**
 * Un itérateur qui utilise l'api "scroll" de Elasticsearch pour parcourir les hits générés par une requête.
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-body.html
 * #request-body-search-scroll
 *
 * @template TValue of object
 * @implements IteratorAggregate<int,TValue>
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ScrollIterator implements IteratorAggregate
{
    /**
     * La requête de recherche en cours.
     *
     * @var SearchRequest
     */
    private $request;

    /**
     * Nombre maximum de hits à retourner.
     *
     * @var int
     */
    private $limit;

    /**
     * Durée de vie du contexte de recherche entre deux appels à scroll().
     *
     * @var string Une chaine de la forme "30s", "2m", etc.
     */
    private $duration;

    /**
     * Construit l'itérateur.
     *
     * @param SearchRequest $request    Requête utilisée pour parcourir les résultats.
     * @param int           $limit      Nombre maximum de hits à retourner (zéro = pas de limite).
     * @param int           $size       Taille des batchs.
     * @param string        $duration   Durée de vie des batchs.
     */
    public function __construct(SearchRequest $request, int $limit = 0, int $size = 1000, string $duration = '2m')
    {
        $this->request = $request;
        $this->limit = $limit;
        $this->request->setSize(($limit > 0 && $limit < $size) ? $limit : $size);
        $this->duration = $duration;
    }

    /**
     * Retourne un générateur qui permet d'itérer sur les hits retournés par la requête.
     *
     * @return Generator<int,TValue> Les clés retournées correspondent au rang du hit (1 pour le premier hit, 2 pour
     * le suivant et ainsi de suite). La valeur associée est un objet hit tel que retourné par Elasticsearch.
     */
    public function getIterator(): Generator
    {
        // S'il y a déjà un scroll en cours sur la requête, on l'annule
        $this->request->scroll('done');

        // Parcourt tous les lots
        $rank = 0;
        for (;;) {
            // Récupère le lot suivant
            $response = $this->request->scroll($this->duration);

            // S'il n'y a plus de résultats, terminé
            if (is_null($response) || empty($hits = $response->getHits())) {
                break;
            }

            // Parcourt tous les hits
            foreach ($hits as $hit) {
                ++$rank;
                yield $rank => $hit;
                if ($this->limit > 0 && $rank >= $this->limit) {
                    break 2;
                }
            }
        }

        // Ferme le contexte de recherche
        $this->request->scroll('done');
    }

    /**
     * Ferme le contexte de recherche elasticsearch lorsque l'itérateur est détruit.
     */
    public function __destruct()
    {
        $this->request->scroll('done');
    }
}
