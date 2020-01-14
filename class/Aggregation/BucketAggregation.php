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

namespace Docalist\Search\Aggregation;

use Docalist\Search\Aggregation;
use Docalist\Search\SearchRequest;
use Docalist\Search\SearchResponse;
use stdClass;
use InvalidArgumentException;

/**
 * Classe de base pour les agrégations de type "bucket".
 *
 * Les agrégations de type bucket peuvent avoir des sous-agrégations.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations-bucket.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
abstract class BucketAggregation extends BaseAggregation
{
    /**
     * Liste des sous-agrégations.
     *
     * @var Aggregation[]
     */
    private $aggregations = [];

    /**
     * Indique si l'agrégation contient des sous-agrégations.
     *
     * @return bool
     */
    final public function hasAggregations(): bool
    {
        return !empty($this->aggregations);
    }

    /**
     * Retourne les sous-agrégations de l'agrégation.
     *
     * @return Aggregation[] Un tableau (éventuellement vide) de la forme name => agrégations.
     */
    final public function getAggregations(): array
    {
        return $this->aggregations;
    }

    /**
     * Définit les sous-agrégations de l'agrégation.
     *
     * @param Aggregation[] $aggregations Un tableau d'agrégations.
     *
     * Si la méthode est appelée avec un tableau vide, la liste des sous-agrégations est vidée.
     */
    final public function setAggregations(array $aggregations): void
    {
        $this->aggregations = [];
        foreach ($aggregations as $aggregation) {
            $this->addAggregation($aggregation);
        }
    }

    /**
     * Ajoute une sous-agrégation à l'agrégation.
     *
     * @param Aggregation $aggregation L'objet Aggregation à ajouter.
     *
     * @throws InvalidArgumentException Si une sous-agrégation avec le même nom existe déjà.
     */
    final public function addAggregation(Aggregation $aggregation): void
    {
        $name = $aggregation->getName();
        if (isset($this->aggregations[$name])) {
            throw new InvalidArgumentException(sprintf('A sub-aggregation named "%s" already exists', $name));
        }

        $this->aggregations[$name] = $aggregation;
    }

    /**
     * Indique si l'agrégation contient la sous-agrégation dont le nom est indiqué.
     *
     * @param string $name Le nom de la sous-agrégation à tester.
     *
     * @return bool
     */
    final public function hasAggregation(string $name): bool
    {
        return isset($this->aggregations[$name]);
    }

    /**
     * Retourne la sous-agrégation dont le nom est indiqué.
     *
     * @param string $name Le nom de la sous-agrégation à retourner.
     *
     * @return Aggregation Retourne la sous-agrégation demandée
     *
     * @throws InvalidArgumentException Si la sous-agrégation indiquée n'existe pas.
     */
    final public function getAggregation($name): Aggregation
    {
        if (!isset($this->aggregations[$name])) {
            throw new InvalidArgumentException(sprintf('Sub-aggregation named "%s" not found', $name));
        }

        return $this->aggregations[$name];
    }

    /**
     * {@inheritDoc}
     *
     * On surcharge getDefinition() pour générer la définition des sous-agrégations éventuelles.
     */
    public function getDefinition(): array
    {
        $definition = parent::getDefinition();

        if ($this->hasAggregations()) {
            $aggs = [];
            foreach ($this->getAggregations() as $name => $aggregation) {
                $aggs[$name] = $aggregation->getDefinition();
            }
            $definition['aggs'] = $aggs;
        }

        return $definition;
    }

    /**
     * {@inheritDoc}
     *
     * On surcharge setSearchRequest() pour transmettre la requête qui a généré l'agrégation à toutes les
     * sous-agrégations.
     */
    final public function setSearchRequest(?SearchRequest $searchRequest): void
    {
        parent::setSearchRequest($searchRequest);
        foreach ($this->getAggregations() as $aggregation) {
            $aggregation->setSearchRequest($searchRequest);
        }
    }

    /**
     * {@inheritDoc}
     *
     * On surcharge setSearchResponse() pour transmettre la réponse obtenue à toutes les sous-agrégations.
     */
    final public function setSearchResponse(?SearchResponse $searchResponse): void
    {
        parent::setSearchResponse($searchResponse);

        foreach ($this->getAggregations() as $aggregation) {
            $aggregation->setSearchResponse($searchResponse);
        }
    }

    /**
     * Prépare le bucket pour qu'il soit affiché.
     *
     * Cette méthode est appellée juste avant que le bucket passé en paramètre ne soit affiché.
     *
     * Si l'aggrégation contient des sous-agrégations, la méthode initialise les sous-agrégations avec les résultats
     * présents dans le bucket.
     *
     * @parama stdClass $bucket Le bucket à préparer.
     *
     * @return stdClass|null Le bucket modifié ou null pour indiquer "ne pas afficher ce bucket".
     */
    protected function prepareBucket(stdClass $bucket): ?stdClass
    {
        foreach ($this->getAggregations() as $name => $aggregation) {
            $aggregation->setResult(isset($bucket->$name) ? $bucket->$name : new stdClass());
        }

        return $bucket;
    }

    /**
     * Retourne la liste des buckets générés par l'agrégation.
     *
     * @return array
     */
    final public function getBuckets(): array
    {
        return $this->getResult('buckets') ?: [];
    }

    /**
     * Retourne le libellé à afficher pour le bucket passé en paramètre.
     *
     * @param stdClass $bucket Les données du bucket : un objet avec des champs comme 'key', 'doc_count', 'from', etc.
     *
     * @return string Le libellé à afficher pour ce bucket.
     */
    public function getBucketLabel(stdClass $bucket): string
    {
        return (string) $bucket->key;
    }
}
