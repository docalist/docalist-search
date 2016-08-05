<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search;

use Docalist\Search\SearchRequest2 as SearchRequest;
use WP_Query;
use Exception;

/**
 * La classe qui gère les recherches.
 */
class SearchEngine
{
    /**
     * La configuration du moteur de recherche
     * (passée en paramètre au constructeur).
     *
     * @var Settings
     */
    protected $settings;

    /**
     * @var SearchRequest la requête adressée à ElasticSearch.
     */
    protected $request;

    /**
     * Les résultats de la recherche.
     *
     * @var SearchResults
     */
    protected $results;

    /**
     * Construit le moteur de recherche.
     *
     * @param Settings $settings
     */
    public function __construct(Settings $settings)
    {
        // Stocke nos paramètres
        $this->settings = $settings;

        // Ne fait rien tant que la recherche n'a pas été activée dans les settings
        // (https://github.com/docalist/docalist/issues/367)
        if (!$this->settings->enabled()) {
            return;
        }

        // Intègre le moteur dans WordPress quand parseQuery() est exécutée
        add_filter('parse_query', [$this, 'onParseQuery']);

        // Crée la requête quand on est sur la page "liste des réponses"
        add_filter('docalist_search_create_request', function (SearchRequest $request = null, WP_Query $query, & $display = true) {
            if (is_null($request) && $query->is_page && $query->get_queried_object_id() === $this->searchPage()) {
                $searchUrl = new SearchUrl($_SERVER['REQUEST_URI']);
                $request = $searchUrl->getSearchRequest();
             // $display = false; // modèle pour panier, export, etc si on ne voulait pas afficher les résultats.
            }

            return $request;
        }, 10, 3);

        // TODO : filtres à virer, utiliser docalist('docalist-search-engine')->xxx()
        add_filter('docalist_search_get_request', [$this, 'request'], 10, 0);
        add_filter('docalist_search_get_results', [$this, 'results'], 10, 0);
        add_filter('docalist_search_get_rank', [$this, 'rank'], 10, 1);
        add_filter('docalist_search_get_hit_link', [$this, 'hitLink'], 10, 1);
    }

    /**
     * Retourne l'ID de la page "liste des réponses" indiquée dans les
     * paramètres de docalist-search.
     *
     * @return int
     */
    public function searchPage()
    {
        return $this->settings->searchpage();
    }

    /**
     * Retourne l'URL de la page "liste des réponses" indiquée dans les
     * paramètres de docalist-search.
     *
     * @return string
     */
    public function searchPageUrl()
    {
        return get_permalink($this->settings->searchpage());
    }

    /**
     * Retourne la requête en cours.
     *
     * @return SearchRequest
     */
    public function request()
    {
        return $this->request;
    }

    /**
     * Retourne les résultats de la requête en cours.
     *
     * @return SearchResults
     */
    public function results()
    {
        return $this->results;
    }

    /**
     * Retourne le rank d'un hit, c'est à dire la position de ce hit (1-based)
     * dans l'ensemble des réponses qui répondent à la requête.
     *
     * @param int $id
     *
     * @return int Retourne la position du hit dans les résultats (le premier
     * est à la position 1) ou zéro si l'id indiqué ne figure pas dans la liste
     * des réponses.
     */
    public function rank($id)
    {
        if ($this->results) {
            return $this->results->position($id) + 1 + ($this->request->page() - 1) * $this->request->size();
        }

        // Le hit demandé ne fait pas partie des réponses
        return 0; // // @todo null ? zéro ? exception ?
    }

    /**
     * Retourne le lien à utiliser pour afficher le hit indiqué tout seul sur
     * une page (i.e. recherche en format long).
     *
     * Le lien retourné est un lien qui permet de relancer une recherche avec
     * start=rank(id) et size=1
     *
     * @param int $id
     */
    public function hitLink($id)
    {
        $url = get_pagenum_link($this->rank($id), false);
        $url = add_query_arg(['size' => 1], $url);

        return $url;
    }

    /**
     * Filtre "parse_query" exécuté lorsque WordPress analyse la requête
     * adressée au site.
     *
     * Remplace la recherche standard de WordPress par notre moteur.
     *
     * Si la requête est une recherche, et qu'il s'agit de la requête
     * principale, on installe les filtres supplémentaires qui vont permettre
     * d'exécuter la recherche (onPostsRequest, onPostsResults, etc.)
     *
     * @param WP_Query $query La requête analysée par WordPress.
     *
     * @return WP_Query La requête, éventuellement modifiée.
     */
    public function onParseQuery(WP_Query & $query)
    {
        $debug = false;

        // Si ce n'est pas la requête principale de WordPress on ne fait rien
        if (! $query->is_main_query()) {
            return $query;
        }

        // Permet aux plugins de créer une requête et d'indiquer s'il faut ou non afficher les résultats
        // obtenus ($displayResults, troisième paramètre du filtre, passé par référence, à true par défaut)
        $displayResults = true;
        $this->request = apply_filters_ref_array('docalist_search_create_request', [null, $query, & $displayResults]);

        // Si on n'a pas de requête à exécuter, on ne fait rien
        if (is_null($this->request)) {
            $debug && print('docalist_search_create_request a retourné null, rien à faire<br />');

            return $query;
        }

        // Sanity check
        if (! $this->request instanceof SearchRequest) {
            throw new Exception('Filter docalist_search_create_request did not return a SearchRequest');
        }

        $debug && print('docalist_search_create_request a retourné une requête, exécution<br />');

        if ($debug) {
            printf(
                "<pre>%s</pre>",
                strtr(json_encode((array)($this->request), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), ['\u0000*' => '', '\u0000' => ''])
            );
        }

        // Exécute la recherche
        $this->results = $this->request->execute();

        $debug && print($this->results->getHitsCount() . ' réponses obtenues<br />');

        // Si on nous a demandé de ne pas afficher les résultats, on a finit
        if (! $displayResults) {
            $debug && print('Le flag $displayResults est à false, terminé<br />');

            return $query;
        }

        $debug && print('Le flag $displayResults est à true, force WP à exécuter comme une recherche<br />');

        // Force WordPress à traiter la requête comme une recherche
        $query->is_search = true;
        $query->is_singular = $query->is_page = false;

        // Indique à WordPress les paramètres de la recherche en cours
        $query->set('posts_per_page', $this->request->getSize());
        $query->set('paged', $this->request->getPage());

        // Empêche WordPress de faire une 2nde requête "SELECT FOUND_ROWS()"
        // (inutile car on a directement le nombre de réponses obtenues)
        $query->set('no_found_rows', true);

        // Permet à get_search_query() de récupérer l'équation de recherche
        $query->set('s', $this->request->getEquation());

        // Construit la liste des ID des réponses obtenues
        $id = [];
        if ($this->results) {
            foreach ($this->results->hits() as $hit) {
                $id[] = $hit->_id;
            }
        }

        // Indique à WordPress la requête SQL à exécuter pour récupérer les posts
        add_filter('posts_request', function ($sql) use ($id) { // !!! pas appellé si suppress_filters=true
            global $wpdb; /* @var $wpdb Wpdb */

            // Aucun hit : retourne sql=null pour que wpdb::query() ne fasse aucune requête
            if (empty($id)) {
                return;
            }

            // Construit une requête sql qui récupére les posts dans l'ordre
            // indiqué par ElasticSearch (http://stackoverflow.com/a/3799966)
            $sql = 'SELECT * FROM %s WHERE ID in (%s) ORDER BY FIELD(id,%2$s)';
            $sql = sprintf($sql, $wpdb->posts, implode(',', $id));

            // TODO : telle que la requête est construite, c'est forcément des
            // posts (pas des commentaires, ou des users, etc.)

            // TODO : supprimer le filtre une fois qu'il a été exécuté
            return $sql;
        });

        // Une fois que WordPress a chargé les posts, vérifie qu'on a tout les
        // documents et indique à WordPress le nombre total de réponses trouvées.
        add_filter('posts_results', function (array $posts = null, WP_Query & $query) use ($id) { //!!! pas appellé si supress_filters=true
            if (count($id) !== count($posts)) {
                echo "<p>WARNING : L'index docalist-search est désynchronisé.</p>";
                // TODO : à améliorer (cf. plugin "simple notices")
            }
            $total = $this->results ? $this->results->getHitsCount() : 0;
            $size = $this->request->getSize();

            $query->found_posts = $total;
            $query->max_num_pages = (int) ceil($total / $size);

            // TODO : supprimer le filtre une fois qu'il a été exécuté

            return $posts;
        }, 10, 2);

        return $query;
    }
}
