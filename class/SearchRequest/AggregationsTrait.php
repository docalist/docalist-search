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
namespace Docalist\Search\SearchRequest;

use Docalist\Search\Aggregation;
use InvalidArgumentException;

/**
 * Gère les agrégations et les facettes qui seront calculées par Elasticsearch au cours de la recherche.
 *
 * Remarque : historiquement, les agrégations étaient gérées sous forme de tableaux (exemple : svb), c'est deprecated
 * et il faut maintenant utiliser les objets Aggregagtion. Néanmoins, ce trait maintient la compatibilité ascendante :
 * plusieurs méthodes ont un paramètre name qui n'est pas utilisé (ni requis).
 *
 */
trait AggregationsTrait
{
    /**
     * Liste des agrégations qui composent la recherche.
     *
     * @var Aggregation[]
     */
    protected $aggregations = [];

    /**
     * Définit les agrégations qui composent la recherche.
     *
     * Si la méthode est appelée sans arguments ou avec un tableau vide, la liste des agrégations est réinitialisée.
     *
     * @param Aggregation[] $aggregations Un tableau d'objets Aggregation.
     *
     * Compatibilité ascendante : si le tableau contient un tableau (et non pas un objet Aggregation), c'est la clé
     * du tableau qui est utilisée comme nom pour l'agrégation.
     *
     * @return self
     */
    public function setAggregations(array $aggregations = [])
    {
        // Supprime toutes les agrégations existantes
        $this->clearAggregations();

        // Ajoute toutes les agrégations passées en paramètre
        foreach($aggregations as $name => $aggregation) {
            // Compatibilité ascendante
            if (is_array($aggregation)) {
                $this->addAggregation($name, $aggregation);
                continue;
            }

            // Mode normal
            $this->addAggregation($aggregation);
        }

        // Ok
        return $this;
    }

    /**
     * Ajoute une agrégation à la liste des agrégations qui composent la recherche.
     *
     * Cette méthode peut être appelée de plusieurs façons :
     * 1. addAggregation(Aggregation $aggregation); // mode normal, appel avec un objet Aggregation
     * 2. addAggregation(string $name, array $aggregation); // compatibilité ascendante, agrégation en tableau
     * 3. addAggregation(string $name, Aggregation $aggregation); // compatibilité ascendante ($name est ignoré)
     *
     * @param string $name Deprecated : nom de l'agrégation, utilisé uniquement si $aggregation est un tableau.
     * @param Aggregation|array $aggregation Un objet Aggregation (deprecated : ou un tableau décrivant l'agrégation).
     *
     * @return self
     */
    public function addAggregation($name = 'deprecated', $aggregation)
    {
        // Compatibilité ascendante (cas 2)
        if (is_array($aggregation)) {
            return $this->addAggregationArray($name, $aggregation);
        }

        // Compatibilité ascendante (cas 3)
        if (is_scalar($name)) {
            return $this->addAggregationObject($aggregation);
        }

        // Mode normal (cas 1)
        return $this->addAggregationObject($name);
    }

    protected function addAggregationObject(Aggregation $aggregation)
    {
        $aggregation->setSearchRequest($this);

        return $this->addAggregationNoCheck($aggregation->getName(), $aggregation);
    }

    protected function addAggregationArray($name, array $aggregation)
    {
        return $this->addAggregationNoCheck($name, $aggregation);
    }

    protected function addAggregationNoCheck($name, $aggregation)
    {
        if (isset($this->aggregations[$name])) {
            throw new InvalidArgumentException("An aggregation named '$name' already exists");
        }

        $this->aggregations[$name] = $aggregation;

        return $this;
    }

    /**
     * Indique si la recherche contient des agrégations.
     *
     * @return bool
     */
    public function hasAggregations()
    {
        return !empty($this->aggregations);
    }

    /**
     * Indique si la recherche contient l'agrégation indiquée.
     *
     * @param string $name Le nom de l'agrégation à tester.
     *
     * @return bool
     */
    public function hasAggregation($name)
    {
        return isset($this->aggregations[$name]);
    }

    /**
     * Retourne les agrégations qui composent la recherche.
     *
     * @return Aggregation[] Un tableau de la forme nom => agrégation.
     *
     * Chaque élément du tableau est un objet Aggregation.
     * (compatibilité ascendante : les éléments peuvent aussi être des tableaux).
     */
    public function getAggregations()
    {
        return $this->aggregations;
    }

    /**
     * Retourne l'agrégation dont le nom est indiqué.
     *
     * @param string $name Le nom de l'agrégation à retourner.
     *
     * @return Agregation|null Retourne l'agrégation demandée ou null si aucune agrégation n'a le nom indiqué.
     * (compatibilité ascendante : l'agrégation retournée peut aussi être un tableau)
     */
    public function getAggregation($name)
    {
        return isset($this->aggregations[$name]) ? $this->aggregations[$name] : null;
    }

    /**
     * Supprime toutes les agrégations qui figurent actuellement dans la requête.
     *
     * @return self
     */
    public function clearAggregations()
    {
        // Supprime toutes les agrégations et leur signale qu'elles ne font plus partie de cette SearchRequest
        foreach(array_keys($this->aggregations) as $name) {
            $this->removeAggregation($name);
        }

        // Ok
        return $this;
    }


    /**
     * Supprime l'agrégation dont le nom est indiqué.
     *
     * Remarque : aucune erreur n'est générée si l'agrégation indiquée n'existe pas.
     *
     * @param string $name Le nom de l'agrégation à supprimer.
     *
     * @return self
     */
    public function removeAggregation($name)
    {
        // Indique à l'agrégation qu'elle ne fait plus partie de cette SearchRequest
        ! is_array($aggregation) && $aggregation->setSearchRequest(null); // is_array : compatibilité ascendante

        // Supprime l'agrégation
        unset($this->aggregations[$name]);

        // Ok
        return $this;
    }

    /**
     * Stocke dans la requête passée en paramètre la clase "aggregations" qui sera envoyée à Elasticsearch.
     *
     * @param array $request Le tableau contenant la requête à modifier.
     */
    protected function buildAggregationsClause(array & $request)
    {
        // Si aucune agrégation n'a été définie, terminé
        if (! $this->hasAggregations()) {
            return;
        }

        $aggregations = [];
        foreach ($this->aggregations as $name => $aggregation) {
            // Compatibilité ascendante : gère les anciennes agrégations sous forme de tableau
            if (is_array($aggregation)) {
                $aggregations[$name] = $aggregation;
                continue;
            }

            /** @var Aggregation $aggregation */
            $aggregations[$name] = $aggregation->getDefinition();

/*
             // Code expérimental pour gérer les agrégations en "OU" en utilisant des post-filters.
             // Non finalisé, en commentaire pour ne pas perdre le travail fait.

             $field = $aggregation->getParameter('field');
             if (TRUE || empty($this->postFilters) || empty($field)) {
                 $request['aggregations'][$name] = $aggregation->getDefinition();
                 continue;
             }

             // echo "<h1>Agg $name porte sur le champ $field</h2>";
             $filters = [];
             foreach($this->postFilters as $filter) {
                 // le filtre est de la forme [type => [ champ => params]], on extrait le nom du champ
                 if ($field !== key(reset($filter))) {
                     $filters[] = $dsl->filter($filter);
                 }
             }
             if (empty($filters)) {
                 $request['aggregations'][$name] = $aggregation->getDefinition();
                 continue;
             }
             //count($filters) > 1 &&
             $filters = $dsl->bool($filters);
             // echo 'Filtres à appliquer à cette agg : <pre>', var_export($filters,true), '</pre>';
             $agg = new FilterAggregation($filters);
             $agg->addAggregation($aggregation);
             $agg->setName($name)->setSearchRequest($this);
             $this->aggregations[$name] = $agg;
             $request['aggregations'][$name] = $agg->getDefinition();
*/
        }

        // Ok, stocke le résultat dans la requête passée en paramètre
        $request['aggregations'] = $aggregations;
    }
}
