<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Search\SearchRequest\Query;

use InvalidArgumentException;

/**
 * Gère les différents types de filtres qui peuvent être ajoutés à la requête.
 *
 * Il existe trois types de filtres disponibles :
 *
 * - 'user-filter' : filtre normal, ajouté par l'utilisateur (en sélectionnant une facette par exemple). Ce
 *    type de filtre est ajouté dans la clause 'filter' de la requête de type 'bool' envoyée à Elasticsearch
 *    et il apparaît dans l'équation de recherche qui est affichée à l'utilisateur.
 *
 * - 'hidden-filter' : filtre ajouté automatiquement par le système pour limiter la recherche à certains types
 *    de contenus, pour exclure les documents auxquels l'utilisateur n'a pas accès (par exemple, uniquement les
 *    notices en statut publish) ou encore pour limiter la recherche à un sous-ensemble de la base (par exemple
 *    uniquement les notices qui figurent dans le panier de l'utilisateur). Ce type de filtre fonctionne comme
 *    un filtre de type normal (il est ajouté dans la clause 'filter' de la requête 'bool') mais il n'apparaît
 *    pas dans l'équation de recherche affichée à l'utilisateur (c'est un filtre 'caché').
 *
 * - 'post-filter' : type de filtre particulier qui est exécuté après l'exécution de la requête. Ce type de
 *    filtre sert à gérer des cas particuliers, par exemples, des aggrégations qui fonctionnent en "ou".
 *    cf. https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-post-filter.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
trait FiltersTrait
{
    /**
     * Liste des filtres à appliquer à la requête.
     *
     * @var array[] Un tableau contenant, pour chaque type de filtre, la liste des filtres définis.
     */
    protected $filters = [
        'user-filter'   => [],
        'hidden-filter' => [],
        'post-filter'   => [],
    ];

    /**
     * Génère une exception si le type de filtre passé en paramètre est invalide.
     *
     * @param string $type Type de filtre à vérifier.
     *
     * @throws InvalidArgumentException
     */
    protected function checkFilterType($type)
    {
        if (isset($this->filters[$type])) {
            return;
        }

        throw new InvalidArgumentException(sprinf(
            'Invalid filter type "%s", expected "%s"',
            $type,
            implode('" or "', array_keys($this->filters))
        ));
    }

    /**
     * Définit les filtres à appliquer pour un type de filtre donné.
     *
     * Si la méthode est appelée sans arguments ou avec un tableau vide, tous les filtres du type indiqué sont
     * supprimés.
     *
     * @param array[] $filters Un tableau contenant les filtres à appliquer.
     *
     * Chaque filtre est lui-même un tableau, en général créé avec le service QueryDSL. Exemple :
     *
     * <code>
     * $request->setFilters([
     *     $dsl->term('type', 'post'),
     *     $dsl->term('status', 'publish'),
     * ]);
     * </code>
     *
     * @param string $type Type de filtre (user-filter, hidden-filter ou post-filter).
     *
     * @return self
     */
    public function setFilters(array $filters = [], $type = 'user-filter')
    {
        $this->checkFilterType($type);
        $this->filters[$type] = [];
        foreach ($filters as $filter) {
            $this->addFilter($filter, $type);
        }

        return $this;
    }

    /**
     * Ajoute un filtre utilisateur à la recherche.
     *
     * @param array $query Un tableau décrivant le filtre, en général créé avec le service QueryDSL. Exemple :
     *
     * <code>
     * $request->addFilter($dsl->term('status', 'publish')
     * </code>
     *
     * @param string $type Type de filtre (user-filter, hidden-filter ou post-filter).
     *
     * @return self
     */
    public function addFilter(array $filter, $type = 'user-filter')
    {
        $this->checkFilterType($type);
        $this->filters[$type][] = $filter;

        return $this;
    }

    /**
     * Indique si la recherche contient des filtres du type indiqué.
     *
     * @param string $type Type de filtre (user-filter, hidden-filter, post-filter ou vide pour "tout type").
     *
     * @return bool
     */
    public function hasFilters($type = '')
    {
        // Si type est vide, on teste tous les types de filtres et on retourne true si on en trouve au moins un
        if (empty($type)) {
            foreach ($this->filters as $filters) {
                if (!empty($filters)) {
                    return true;
                }
            }
            return false;
        }

        // Test sur un type de filtre particulier
        $this->checkFilterType($type);
        return !empty($this->filters[$type]);
    }

    /**
     * Définit la liste des filtres à appliquer pour un type de filtre donné.
     *
     * @param string $type Type de filtre (user-filter, hidden-filter ou post-filter).
     *
     * @return array[]
     */
    public function getFilters($type = 'user-filter')
    {
        $this->checkFilterType($type);

        return $this->filters[$type];
    }
}
