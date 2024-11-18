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

namespace Docalist\Search;

use InvalidArgumentException;
use Docalist\Search\SearchRequest\QueryTrait;
use Docalist\Search\SearchRequest\PageTrait;
use Docalist\Search\SearchRequest\SizeTrait;
use Docalist\Search\SearchRequest\SortTrait;
use Docalist\Search\SearchRequest\SourceTrait;
use Docalist\Search\SearchRequest\SearchUrlTrait;
use Docalist\Search\SearchRequest\AggregationsTrait;
use Docalist\Search\SearchRequest\EquationTrait;
use Docalist\Search\Aggregation;

/**
 * Une requête de recherche adressée à ElasticSearch.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class SearchRequest
{
    use QueryTrait, PageTrait, SizeTrait, SortTrait, SourceTrait, SearchUrlTrait, AggregationsTrait, EquationTrait;

    /**
     * Valeur par défaut pour le nombre de réponses par page (size).
     *
     * @var integer
     */
    const DEFAULT_SIZE = 10; // Devrait être dans SizeTrait mais un trait ne peut pas définir de constantes

    /**
     * Indique si la requête exécutée a des erreurs.
     *
     * Initialisé lorsque execute() est appelée.
     *
     * @var bool
     */
    protected $hasErrors = false;

    /**
     * Pour une requête de type "scroll", identifiant à utiliser pour obtenir le prochain lot.
     *
     * @var string
     */
    protected $scrollId = '';

    // -------------------------------------------------------------------------------
    // Constructeur
    // -------------------------------------------------------------------------------

    /**
     * Construit une nouvelle requête de recherche.
     *
     * @param array $types Optionnel, liste des contenus sur lesquels portera la recherche (par défaut, tous).
     */
    public function __construct(array $types = [])
    {
        $this->setTypes($types);
    }

    /**
     * Indique si la requête de recherche est vide
     *
     * La requête est considérée comme vide si elle ne contient aucune requête et aucun filtre (les agrégations
     * éventuelles et les paramètres de la recherche comme size ou page ne sont pas pris en compte).
     *
     * @return bool
     */
    public function isEmptyRequest()
    {
        return !$this->hasQueries() && !$this->hasFilters();
    }

    // -------------------------------------------------------------------------------
    // Exécution
    // -------------------------------------------------------------------------------
    protected function buildRequest()
    {
        $request = [];

        // ES >=7 ne compte que les 10000 premiers hits, ajoute track_total_hits pour avoir le nombre exact
        if (version_compare(docalist()->string('elasticsearch-version'), '6.99', '>')) {
            $request['track_total_hits'] =  true;
        }

        // Requête à exécuter
        $this->buildQueryClause($request);

        // post filters
        $this->buildPostFilterClause($request);

        // Numéro du premier hit
        $this->buildPageClause($request);

        // Nombre de réponses par page
        $this->buildSizeClause($request);

        // Tri
        $this->buildSortClause($request);

        // Champs _source à retourner
        $this->buildSourceClause($request);

        // Expliquer les hits obtenus
        // $this->explainHits && $request['explain'] = true;

        // Agrégrations
        $this->buildAggregationsClause($request);

        // Excerpts et mise en surbrillance des mots trouvés
/* // test
        $request['highlight'] = [
            //'encoder' => 'html',
            'type' => 'plain',
            'number_of_fragments' => 10,
            'fragment_size' => 80,
            'tags_schema' => 'styled',
            'fragmenter' => 'span',
            'fields' => [
                ['titre' => new \stdClass()],
                ['poste' => new \stdClass()],
                ['profil' => new \stdClass()],
            ]
        ];
*/
        // Ok
        return $request;
    }

    /**
     * Envoie la requête au serveur elasticsearch passé en paramètre et retourne les résultats obtenus.
     *
     * @param array $options Options de la recherche. Les valeurs possibles sont les suivantes :
     *
     * - search_type : mode d'exécution de la requête sur les différents shards du cluster elasticsearch.
     *   ('query_then_fetch' ou 'dfs_query_then_fetch').
     *   cf. https://www.elastic.co/guide/en/elasticsearch/reference/master/search-request-search-type.html
     *
     * - filter_path : filtres sur les informations à retourner dans la réponse.
     *   cf. https://www.elastic.co/guide/en/elasticsearch/reference/master/common-options.html#_response_filtering
     *
     * @return SearchResponse|null Un objet SearchResponse décrivant les résultats de la recherche ou null si
     * elasticsearch a généré une erreur.
     */
    public function execute(array $options = [])
    {
        // Construit la requête
        $request = $this->buildRequest();

        // Construit les paramètres de la recherche à partir des options indiquées
        $url = '/{index}/_search' . $this->getExecuteOptions($options);

        // Exécute la requête
        /** @var ElasticSearchClient $es */
        $es = docalist(ElasticSearchClient::class);
        $data = $es->get($url, $request);
        if (isset($data->error)) {
            $this->hasErrors = true;

            return null;
        }

        // Si un scroll_id a été retourné (requête de type "scroll"), on le stocke
        if (isset($data->_scroll_id)) {
            // Si on avait déjà un scrollId on le libère
            !empty($this->scrollId) && $this->scroll('done');

            // Stocke l'id
            $this->scrollId = $data->_scroll_id;
        }

        // Crée l'objet SearchResponse (sans données pour le moment)
        $this->hasErrors = false;
        $searchResponse = new SearchResponse($this);

        // Fournit le résultat obtenu à chaque agrégation et remplace le résultat brut par l'objet Aggregation
        foreach ($this->aggregations as $name => $aggregation) {
            if ($aggregation instanceof Aggregation) {
                $result = $data->aggregations->$name ?: null;
                $aggregation->setSearchResponse($searchResponse);
                $aggregation->setResult($result);
                $data->aggregations->$name = $aggregation;
            }
        }

        // Fournit les données finales à l'objet SearchResponse
        $searchResponse->setData($data);

        // Retourne les résultats
        return $searchResponse;
    }

    /**
     * Exécute la requête en utilisant l'API "scroll" de Elasticsearch.
     *
     * Une requête de type "scroll" travaille sur l'index Elasticsearch tel qu'il est au moment où la
     * recherche est lancée (une espèce de snapshot). Cela permet de parcourir les résultats obtenus
     * (potentiellement un très grand nombre) sans être affecté par les modifications qui peuvent
     * survenir dans l'index pendant le parcourt des résultats.
     *
     * Chaque appel à la méthode scroll() retourne le prochain lots de résultat, qui est utilisable
     * pendant la durée indiquée en paramètre.
     *
     * Lorsque l'itération est terminée, vous devez appeller scroll('done') pour libérer le contexte
     * de recherche créé par Elasticsearch.
     *
     * @param string $duration Soit une durée de la forme "30s" ou "2m" pour obtenir les hits suivants,
     * soit le mot-clé "done" pour libérer le contexte de recherche et termine la scroll request.
     *
     * @return SearchResponse|null Quand scroll() est appellée avec une durée, elle retourne un objet
     * SearchResponse contenant les hits suivants ou null si Elasticsearch a généré une erreur.
     * Quand scroll() est appellée avec le paramètre "done", elle retourne null.
     */
    public function scroll(string $duration = '10s'): ?SearchResponse
    {
        // scroll('done') permet de libérer le contexte de recherche
        if ($duration === 'done') {
            if (!empty($this->scrollId)) { // no-op si aucun scroll en cours
                /** @var ElasticSearchClient $es */
                $es = docalist(ElasticSearchClient::class);
                $es->delete('/_search/scroll', ['scroll_id' => $this->scrollId]);
                $this->scrollId = '';
            }

            return null;
        }

        // scroll() avec une durée permet de créer ou de prolonger le contexte de recherche
        if ((bool) preg_match('~^\d+[ms]$~', $duration)) {
            // On accepte uniquement des secondes ou des minutes, et sans espace
            // cf. https://www.elastic.co/guide/en/elasticsearch/reference/current/common-options.html#time-units

            // Au premier appel, on execute la requête avec l'option "scroll" et on stocke le scroll_id généré
            if (empty($this->scrollId)) {
                return $this->execute(['scroll' => $duration]); // initialise $this->scrollId
            }

            // Pour les appels suivants, on utilise l'api scroll avec l'id en cours et on stocke le nouvel id
            /** @var ElasticSearchClient $es */
            $es = docalist(ElasticSearchClient::class);
            $data = $es->get('/_search/scroll', [
                'scroll_id' => $this->scrollId,
                'scroll'    => $duration,
            ]);

            if (isset($data->error)) {
                $this->hasErrors = true;
                $this->scrollId = '';

                throw new InvalidArgumentException('Scroll context has expired');
            }

            $this->hasErrors = false;
            $this->scrollId = $data->_scroll_id;

            return new SearchResponse($this, $data);
        }

        // On nous a passé n'import quoi
        throw new InvalidArgumentException('Expected scroll duration or "done"');
    }

    /**
     * Retourne la query-string à ajouter à l'url /_search en fonction des options passées en paramètre.
     *
     * @param array $options
     *
     * @return string
     */
    protected function getExecuteOptions(array $options)
    {
        // Construit les paramètres de la recherche à partir des options indiquées
        $queryString = '';

        // https://www.elastic.co/guide/en/elasticsearch/reference/master/search-request-search-type.html
        if (isset($options['search_type'])) {
            $option = $options['search_type'];
            unset($options['search_type']);
            $allowed = ['query_then_fetch', 'dfs_query_then_fetch'];
            if (!in_array($option, $allowed)) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid search_type "%s", expected "%s"',
                    $option,
                    implode('" or "', $allowed)
                ));
            }
            $queryString .= '&search_type=' . urlencode($option);
        }

        // https://www.elastic.co/guide/en/elasticsearch/reference/master/common-options.html#_response_filtering
        if (isset($options['filter_path'])) {
            $option = $options['filter_path'];
            unset($options['filter_path']);
            $queryString .= '&filter_path=' . urlencode($option);
        }

        // scroll : https://www.elastic.co/guide/en/elasticsearch/reference/master/search-request-scroll.html
        if (isset($options['scroll'])) {
            $option = $options['scroll'];
            unset($options['scroll']);
            $queryString .= '&scroll=' . urlencode($option);
        }

        // preference : https://www.elastic.co/guide/en/elasticsearch/reference/master/search-request-preference.html

        // explain : pas en querystring
        // https://www.elastic.co/guide/en/elasticsearch/reference/master/search-request-explain.html
        // version : pas en querystring
        // https://www.elastic.co/guide/en/elasticsearch/reference/master/search-request-version.html

        // Génère une exception s'il reste des options qu'on ne gère pas
        if (!empty($options)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported search options "%s"',
                implode('" and "', array_keys($options))
            ));
        }

        // Finalise la querystring
        $queryString && $queryString[0] = '?';

        // Ok
        return $queryString;
    }

    /**
     * Indique si la dernière exécution de la requête a généré des erreurs.
     *
     * N'a de sens que si execute() a déjà été appelé.
     *
     * @return bool
     */
    public function hasErrors()
    {
        return $this->hasErrors;
    }

    /**
     * Indique si la requête est valide.
     *
     * La méthode utilise l'API validate de Elasticsearch et retourne un booléen qui indique si la requête est
     * valide ou non.
     *
     * Si vous passez un paramètre, vous obtenez une explication supplémentaire :
     *
     * - si la requête est invalide : le message d'erreur de l'exception Elasticsearch qui indique pourquoi la
     *   requête a échoué.
     * - si la requête est valide : une simili équation de recherche qui indique précisément comment
     *   Elasticsearch a compris la requête (tokens recherchés, combinatoire, etc.)
     *
     * @param string $explanation
     *
     * @return bool
     */
    public function validate(& $explanation = '')
    {
        // Construit la requête
        $request = [];
        $this->buildQueryClause($request);

        // Construit l'url
        $url = '/{index}/_validate/query?explain=true&rewrite=true';

        // Exécute la requête
        /** @var ElasticSearchClient $es */
        $es = docalist(ElasticSearchClient::class);
        $response = $es->get($url, $request);

        // Normalement on a toujours le champ "explanations"
        if (! (isset($response->explanations) || empty($response->explanations))) {
            $explanation = json_encode(
                $response,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_PRETTY_PRINT
            );

            return false;
        }

        // Récupère la première explication donnée (on en a une seule car on n'a pas mis "&all_shards=true")
        $explanation = reset($response->explanations);

        // Cas d'une requête valide
        if (isset($explanation->valid) && $explanation->valid === true) {
            $explanation = $this->formatExplanation($explanation->explanation);

            return true;
        }

        // Cas d'une requête invalide
        $explanation = $explanation->error;

        return false;
    }

    /**
     * Formatte et indente l'équation de recherche fournie par validate().
     *
     * No-op pour le moment / non implémenté.
     *
     * @param string $explanation
     *
     * @return string
     */
    protected function formatExplanation($explanation)
    {
        return $explanation;
    }
}
