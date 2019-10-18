<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
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
    protected $aggregations = [];

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
     * Si la méthode est appelée sans arguments ou avec un tableau vide, la liste des sous-agrégations est vidée.
     *
     * @return self
     */
    public function setAggregations(array $aggregations = [])
    {
        $this->aggregations = [];
        foreach ($aggregations as $aggregation) {
            $this->addAggregation($aggregation);
        }

        return $this;
    }

    /**
     * Ajoute une sous-agrégation à l'agrégation.
     *
     * @param Aggregation $aggregation L'objet Aggregation à ajouter.
     *
     * @return self
     */
    public function addAggregation(Aggregation $aggregation)
    {
        $name = $aggregation->getName();
        if (isset($this->aggregations[$name])) {
            throw new InvalidArgumentException(sprintf('A sub-aggregation named "%s" already exists', $name));
        }

        $this->aggregations[$name] = $aggregation;

        return $this;
    }

    /**
     * Indique si l'agrégation contient la sous-agrégation dont le nom est indiqué.
     *
     * @param string $name Le nom de la sous-agrégation à tester.
     *
     * @return bool
     */
    public function hasAggregation($name)
    {
        return isset($this->aggregations[$name]);
    }

    /**
     * Retourne la sous-agrégation dont le nom est indiqué.
     *
     * @param string $name Le nom de l'agrégation à retourner.
     *
     * @return Aggregation|null Retourne la sous agrégation demandée ou null si l'agrégation indiqué n'existe pas.
     */
    public function getAggregation($name)
    {
        return isset($this->aggregations[$name]) ? $this->aggregations[$name] : null;
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
    public function setSearchRequest(?SearchRequest $searchRequest): void
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
    public function setSearchResponse(?SearchResponse $searchResponse): void
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
    protected function prepareBucket(stdClass $bucket)
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
    public function getBuckets()
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
    public function getBucketLabel(stdClass $bucket)
    {
        return (string) $bucket->key;
    }

    /**
     * Retourne le bucket qui a la clé indiquée.
     *
     * @param string $key La clé recherchée.
     *
     * @return stdClass|null Le bucket correspondant ou null si la clé indiquée n'existe pas.
     */
    public function getBucket($key)
    {
        foreach ($this->getBuckets() as $bucket) {
            if ($key === $bucket->key) {
                return $bucket;
            }
        }

        return null;
    }
}
