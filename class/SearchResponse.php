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

namespace Docalist\Search;

use stdClass;
use Docalist\Search\SearchRequest;

/**
 * Le résultat d'une requête de recherche adressée à ElasticSearch.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class SearchResponse
{
    /**
     * La requête qui a généré les résultats.
     *
     * @var SearchRequest
     */
    protected $searchRequest;

    /**
     * La réponse brute retournée par ElasticSearch.
     *
     * @var stdClass
     */
    protected $data;

    /**
     * Initialise l'objet à partir de la réponse retournée par elasticsearch.
     *
     * @param SearchRequest|null    $request    Optionnel, l'objet SearchRequest qui a généré ces résultats.
     * @param stdClass|null         $data       Optionnel, la réponse brute retournée par ElasticSearch.
     */
    public function __construct(SearchRequest $request = null, stdClass $data = null)
    {
        $this->searchRequest = $request;
        $this->data = $data;
    }

    /**
     * Définit la requête qui a généré cet objet résultat.
     *
     * @return SearchRequest
     */
    public function setSearchRequest(SearchRequest $request)
    {
        $this->searchRequest = $request;

        return $this;
    }

    /**
     * Retourne la requête qui a généré cet objet résultat.
     *
     * @return SearchRequest
     */
    public function getSearchRequest()
    {
        return $this->searchRequest;
    }

    /**
     * Définit les données brutes retournées par elasticsearch.
     *
     * @param stdClass $data La réponse brute générée par ElasticSearch.
     *
     * @return self
     */
    public function setData(stdClass $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Retourne les données brutes retournées par elasticsearch.
     *
     * @return stdClass
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Indique si la requête a généré un time out.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/search-request-body.html#_parameters_4
     *
     * @return bool
     */
    public function isTimedOut()
    {
        return isset($this->data->timed_out) ? $this->data->timed_out : false;
    }

    /**
     * Indique si la recherche a été arrêtée avant d'avoir collecté toutes les réponses.
     *
     * Ce flag n'existe que si la requête exécutée contenant un paramètre "terminate_after".
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/search-request-body.html#_parameters_4
     *
     * @return bool
     */
    public function isTerminatedEarly()
    {
        return isset($this->data->terminated_early) ? $this->data->terminated_early : false;
    }

    /**
     * Retourne le temps (en millisecondes) mis par ElasticSearch pour exécuter la requête.
     *
     * Ce temps correspond au temps d'exécution de la requête sur les différents shards.
     *
     * Il ne comprend le temps passé au cours des étape suivantes :
     *
     * - sérialisation de la requête (nous)
     * - envoi de la requête à Elastic Search (réseau)
     * - désérialisation de la requête (par ES)
     *
     * - sérialisation de la réponse (ES)
     * - transit de la réponse (réseau)
     * - désérialisation de la réponse
     *
     * @return int durée en millisecondes
     *
     * @link @see http://elasticsearch-users.115913.n3.nabble.com/query-timing-took-value-and-what-I-m-measuring-tp4026185p4026226.html
     */
    public function getTook()
    {
        return isset($this->data->took) ? $this->data->took : 0;
    }

    /**
     * Retourne le nombre total de shards qui ont exécuté la requête.
     *
     * @return int
     */
    public function getTotalShards()
    {
        return isset($this->data->_shards->total) ? $this->data->_shards->total : 0;
    }

    /**
     * Retourne le nombre de shards qui ont réussi à exécuter la requête.
     *
     * @return int
     */
    public function getSuccessfulShards()
    {
        return isset($this->data->_shards->successful) ? $this->data->_shards->successful : 0;
    }

    /**
     * Retourne le nombre de shards qui ont échoué à exécuter la requête.
     *
     * @return int
     */
    public function getFailedShards()
    {
        return isset($this->data->_shards->failed) ? $this->data->_shards->failed : 0;
    }

    /**
     * Retourne le nombre total de documents qui répondent à la requête exécutée.
     *
     * @return int
     */
    public function getHitsCount()
    {
        // Vérifie qu'on a le nombre total de hits
        if (! isset($this->data->hits->total)) {
            return 0;
        }

        // Avant Elasticsearch version 7, c'était juste un entier
        if (is_int($this->data->hits->total)) {
            return $this->data->hits->total;
        }

        // Depuis Elasticsearch version 7, c'est un objet qui contient un champ value
        if (isset($this->data->hits->total->value) && is_int($this->data->hits->total->value)) {
            return $this->data->hits->total->value;
        }

        // Format non reconnu
        return 0;
    }

    /**
     * Retourne le score maximal obtenu par la meilleure réponse.
     *
     * @return float
     */
    public function getMaxScore()
    {
        return isset($this->data->hits->max_score) ? $this->data->hits->max_score : 0.0;
    }

    /**
     * Retourne la liste des réponses obtenues.
     *
     * @return array Chaque réponse est un objet contenant les propriétés suivantes :
     *
     * _id : numéro de référence de l'enregistrement
     * _score : score obtenu
     * _index : nom de l'index ElasticSearch d'où provient le hit
     * _type : type du hit
     */
    public function getHits()
    {
        return isset($this->data->hits->hits) ? $this->data->hits->hits : [];
    }

    /**
     * Retourne les agrégations obtenues.
     *
     * @return array
     */
    public function getAggregations()
    {
        return isset($this->data->aggregations) ? (array) $this->data->aggregations : [];
    }

    /**
     * Indique si l'agrégation indiquée existe.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasAggregation($name)
    {
        return isset($this->data->aggregations->$name);
    }

    /**
     * Retourne une agrégation.
     *
     * @param string $name
     *
     * @return Aggregation|array|null
     */
    public function getAggregation($name)
    {
        return isset($this->data->aggregations->$name) ? $this->data->aggregations->$name : null;
    }

    /**
     * Génère une liste de liens permettant de parcourir les pages de résultats obtenus.
     *
     * Cette méthode est similaire à la fonction paginate_links() de WordPress mais elle gère correctement les
     * paramètres de query-string qui contiennent des points (paginate_links utilise parse_str qui les convertit
     * en tirets).
     *
     * @param array $options
     *
     * - 'prev_next'    (bool)      Indique s'il faut inclure les liens "Précédent" / "Suivant" (true par défaut).
     * - 'prev_text'    (string)    Texte du lien "Précédent" ("« Précédent" par défaut).
     * - 'next_text'    (string)    Texte du lien "Suivant" ("Suivant »" par défaut).
     * - 'end_size'     (int)       Nombre de liens au début et à la fin de la liste (1 par défaut).
     * - 'mid_size'     (int)       Nombre de liens de chaque côté de la page en cours (2 par défaut).
     *
     * @return array
     */
    public function getPagesLinks(array $options = []): array
    {
        // Récupère les variables dont on a besoin
        $total = $this->getHitsCount();                             // Nombre total de hits
        $searchRequest = $this->getSearchRequest();                 // Requête en cours
        $searchUrl = $searchRequest->getSearchUrl();                // SearchUrl en cours
        $size = $searchRequest->getSize();                          // Nombre de hits par page
        $lastPage = ($size === 0) ? 1 : (int) ceil($total / $size); // Numéro de la dernière page
        $current = min($searchRequest->getPage(), $lastPage);       // Numéro de la page en cours
        $links = [];                                                // Liste des liens générés

        // Si on a moins de deux pages de résultats, terminé
        if ($lastPage < 2) {
            return [];
        }

        // Valide les options passées en paramètre
        $prevNext = (bool) ($options['prev_next'] ?? true);
        $prevText = (string) ($options['prev_text'] ?? __('&laquo; Précédent', 'docalist-search'));
        $nextText = (string) ($options['next_text'] ?? __('Suivant &raquo;', 'docalist-search'));
        $endSize = max(1, (int) ($options['end_size'] ?? 1));
        $midSize = (int) ($options['mid_size'] ?? 2);

        // Un helper qui simplifie la création des liens
        $link = function (int $page, string $text = '', string $css = '') use ($searchUrl) {
            return sprintf(
                '<a class="%s" href="%s">%s</a>',
                rtrim('page-numbers ' . $css),
                htmlspecialchars($searchUrl->getUrlForPage($page)),
                empty($text) ? (string) $page : $text
            );
        };

        // Bouton "Précédent"
        if ($prevNext && $current > 1) {
            $links['previous'] = $link($current - 1, $prevText, 'prev');
        }

        // End size de gauche
        if ($endSize < $current) {
            for ($page = 1; $page <= $endSize; $page++) {
                $links[$page] = $link($page);
            }
            if ($endSize < $current - $midSize - 1) {
                $links['left-dots'] = '<span class="page-numbers dots">&hellip;</span>';
            }
        }

        // Mid size de gauche
        for ($page = max(1, $current - $midSize); $page < $current; $page++) {
            $links[$page] = $link($page);
        }

        // Page en cours
        $links['current'] = sprintf('<span aria-current="page" class="page-numbers current">%d</span>', $current);

        // Mid size de droite
        for ($page = $current + 1; $page <= min($lastPage, $current + $midSize); $page++) {
            $links[$page] = $link($page);
        }

        // End size de droite
        if ($current < $lastPage - $endSize + 1) {
            if ($current + $midSize < $lastPage - $endSize) {
                $links['right-dots'] = '<span class="page-numbers dots">&hellip;</span>';
            }
            for ($page = $lastPage - $endSize + 1; $page <= $lastPage; $page++) {
                $links[$page] = $link($page);
            }
        }

        // Bouton "Suivant"
        if ($prevNext && $current < $lastPage) {
            $links['next'] = $link($current + 1, $nextText, 'next');
        }

        // Ok
        return $links;
    }
}
