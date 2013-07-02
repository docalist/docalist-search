<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2012, 2013 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */
namespace Docalist\Search;
use Docalist\AbstractPlugin, Docalist\QueryString;
use StdClass, Exception;
use WP_Query;

/* Documentation : doc/search-design.md */

/**
 * Plugin Docalist Search.
 */
class Search extends AbstractPlugin {
    /**
     * @var SearchRequest la requête adressée à ElasticSearch
     */
    protected $request;

    /**
     * {@inheritdoc}
     */
    public function register() {
        // Configuration du plugin
        $this->add(new Settings);

        // Client ElasticSearch
        $this->add(new ElasticSearch);

        // Back office
        add_action('admin_menu', function() {
            // Configuration
            $this->add(new SettingsPage);

            // Outils
            $this->add(new Tools);
        });

        // Déclare notre widget "Search Facets"
        add_action('widgets_init', function() {
            register_widget( __NAMESPACE__ . '\FacetsWidget' );
        });

        // Si l'utilisateur n'a pas encore activé la recherche, terminé
        if (! $this->setting('general.enabled')) {
            return;
        }

        // Remplace la recherche standard de WordPress par notre moteur.
        // WordPress considère que la requête est une recherche seulement si
        // "s" figure en query string ET qu'il est rempli. Dans notre cas, on
        // considère qu'il s'agit d'une recherche même si s est vide (dans ce
        // cas une recherche "*" sera exécutée).
        // Si la requête est une recherche, et qu'il s'agit de la requête
        // principale, on installe nos filtres.
        add_filter('parse_query', function(WP_Query & $query) {
            // Si c'est une sous-requête (query_posts, etc.) on ne fait rien
            if (! $query->is_main_query()) return $query;

            // Si ce n'est pas uen recherche, on ne fait rien
            if (! array_key_exists('s', $_GET)) return $query;

            // La requête est une recherche
            $query->is_search = true;

            // Fournit à WordPress la requête SQL à exécuter (les ID retournés par ES)
            add_filter('posts_request', function($sql, WP_Query &$query){
                return $this->onPostsRequest($sql, $query);
            }, 10, 2);

            // Indique à WordPress le nombre de réponses obtenues
            add_filter('posts_results', function(array $posts = null, WP_Query &$query){
                return $this->onPostsResults($posts, $query);
            }, 10, 2);

            return $query;
        }, 10, 1);

        // TODO : revoir quelle est la priorité la plus adaptée pour les filtres
    }

    /**
     * Intercepte la recherche standard de WordPress et retourne une requête
     * SQL contenant les hits obtenus.
     *
     * Cette méthode est appellée quand WordPress exécute le filtre
     * "posts_request" (juste après avoir analysé l'url demandée) et seulement
     * si on a détecté que la requête en cours était une recherche.
     *
     * @param string $sql La requête SQL construite par WordPress.
     * @param WP_Query $query L'objet Query construit par WordPress.
     *
     * @return string|null Retourne une requête sql de la forme :
     *
     * <code>
     * SELECT * FROM wp_posts WHERE ID in (<IDs>) ORDER BY FIELD(id, <IDs>)
     * <code>
     *
     * dans laquelle <IDs> représentent la liste des post_ids retournés par
     * ElasticSearch.
     *
     * Si la recherche ElasticSearch est infructueuse (aucune réponse, équation
     * erronnée, serveur qui ne répond pas, etc.) la méthode retourne null.
     * Dans ce cas, WordPress ne va exécuter aucune requête sql (cf. le code
     * source de WPDB::get_results()) et va afficher la page "aucune réponse".
     */
    private function onPostsRequest($sql, WP_Query & $query) {
        /* @var $wpdb Wpdb */
        global $wpdb;

        // Empêche wp de faire ensuite une requête "SELECT FOUND_ROWS()"
        $query->query_vars['no_found_rows'] = true;

        // Construit la requête qu'on va envoyer à ElasticSearch
        $args = QueryString::fromCurrent();
        $this->request = new SearchRequest($this->get('elasticsearch'), $args);

        // Synchronize size et posts_per_page pour que le pager fonctionne
        $size = $this->request->size();
        if ($size !== $query->get('posts_per_page')) {
            $query->set('posts_per_page', $size);
        }

        // Synchronize page et paged pour que le pager fonctionne
        $page = $this->request->page();
        if ($page !== $query->get('paged')) {
            $query->set('paged', $page);
        }

        // Exécute la recherche
        try {
            $results = $this->request->execute();
        } catch (Exception $e) {
            return null;
        }

        // Récupère les hits obtenus
        $hits = $results->hits();

        // Aucune réponse : retourne sql=null pour que wpdb::query() ne fasse aucune requête
        if (empty($hits)) {
            return null;
        }

        // Construit la liste des ID des réponses obtenues
        $id = array();
        foreach($hits as $hit) {
            $id[] = $hit->_id;
        }

        // Construit une requête sql qui récupére les posts dans l'ordre
        // indiqué par ElasticSearch (http://stackoverflow.com/a/3799966)
        $sql = 'SELECT * FROM %s WHERE ID in (%s) ORDER BY FIELD(id,%2$s)';
        $sql = sprintf($sql, $wpdb->posts, implode(',', $id));

        return $sql;
    }

    /**
     * Indique à WordPress le nombre exact de réponses obtenues et le nombre
     * total de pages de réponses possibles.
     *
     * Cette méthode est appellée quand WordPress exécute le filtre
     * "posts_results" (juste après avoir exécuté la requête SQL) quand la
     * requête est une recherche.
     *
     * La méthode se contente d'initialiser les variables "found_posts" et
     * "max_num_pages" de l'objet WP_Query passé en paramètre.
     *
     * @param array $posts La liste des posts obtenus lors de la recherche.
     * @param WP_Query $query Les paramètres de la requête en cours.
     *
     * @return array $posts Retourne inchangé le tableau passé en paramètre
     * (seul $query nous intéresse).
     */
    private function onPostsResults(array $posts = null, WP_Query &$query) {
        /* @var $results Results */
        $results = $this->request->results();

        $total = $results ? $results->total() : 0;
        $size = $this->request->size();

        $query->found_posts = $total;
        $query->max_num_pages = (int) ceil($total / $size);

        return $posts;
    }

    /**
     * Retourne la requête en cours.
     *
     * @return SearchRequest
     */
    public function request() {
        return $this->request;
    }

    /**
     * Retourne la liste des types de contenu disponibles.
     */
    protected function NUtypes() {
        $facets = apply_filters("dclsearch_facets", array());
    }

    /**
     * Retourne la liste des filtres disponibles.
     */
    protected function NUfilters() {
        $facets = apply_filters("dclsearch_facets", array());
    }

    /**
     * Génère un code html contenant la liste des filtres de recherche actifs.
     *
     * @param string $format Une chaine au format sprintf décrivant le code générer
     * pour chaque filtre. Par défaut, la chaine suivante est utilisée :
     *
     * <code>
     * <a href="%3$s" class="%4$s">%2$s</a>
     * </code>
     *
     * Cette chaine recevra en paramètre :
     * - %1$s : le nom du filtre (par exemple "author.keyword")
     * - %2$s : la valeur du filtre
     * - %3$s : l'url permettant de désactiver le filtre
     * - %4$s : un nom de classe css de la forme "filter-xxx" contruit à partir du
     *   nom du filtre (par exemple "filter-author-keyword").
     *
     * @param string $separator Code html à insérer entre deux filtre (", " par
     * défaut).
     *
     * @param string $wrapper Une chaine au format sprintf décrivant le code html à
     * générer pour le containeur contenant les filtres en cours. Par défaut, c'est :
     *
     * <code>
     * <p class="current-search-filters">Filtres en cours : %s.</p>
     * </code>
     *
     * Cette chaine reçoit en paramètre :
     * - %s : la liste des filtres en cours.
     *
     * @return string Retourne le code html généré ou une chaine vide si aucun
     * filtre n'est actif.
     */
    public function theCurrentFilters($format = null, $separator = null, $wrapper = null) {
        /* @var $request Docalist\Search\SearchRequest */
        $request = $this->request();

        // Retourne une chaine vide si on n'a aucun filtre actif
        $request && $filters = $request->filters();
        if (empty($filters)) return '';

        // Format par défaut
        if (is_null($format)) {
            $format = '<a href="%3$s" class="%4$s">%2$s</a>';
        }

        // Séparateur par défaut
        if (is_null($separator)) {
            $separator = ', ';
        }

        // Wrapper par défaut
        if (is_null($wrapper)) {
            $label = __('Filtres en cours : %s', 'docalist-biblio');
            $wrapper = sprintf('<p class="current-search-filters">%s.</p>', $label);
        }

        $currentUrl = QueryString::fromCurrent();
        $result = '';
        $first = true;
        foreach($filters as $filter => $values) {
            $class = 'filter-' . strtr($filter, '.', '-');
            foreach ($values as $value) {
                $url = $currentUrl->copy()->clear("filter.$filter", $value)->encode();
                if ($first) $first = false; else $result .= $separator;
                $result .= sprintf($format, $filter, $value, $url, $class);
            }
        }

        return sprintf($wrapper, $result);
    }

    /**
     * Retourne la liste des facettes disponibles.
     */
    public function facets() {
        // 1. obtenir toutes les facettes qui existent
        // 2. get_options pour récupérer les facettes activées par l'administrateur
        // 3. trier selon l'ordre indiqué par l'administrateur
        // 4. gérer les droits

        // Etat initial des facettes :
        // - normal :       facette visible, affiche SIZE éléments
        // - collapsed :    facette repliée (on a size éléments en display none) visible sur clic
        // - closed :       affiche seulement le titre (facette non calculée). quand on clique, nouvelle requête avec facet.name=size
        // - hidden :       facette n'est pas affichée, mais si elle est demandée en query string, on l'affiche)
        // - disabled :     cette facette n'est jamais affichée

        // Propriétés des facettes
        // http://www.elasticsearch.org/guide/reference/api/search/facets/
        // https://github.com/elasticsearch/elasticsearch/blob/master/src/main/java/org/elasticsearch/search/facet/FacetParseElement.java
        // Quel que soit le type : (cf )
        //     <nom de champ>
        //     facet_filter
        //     global
        //     mode : collector ou post - non documenté : http://elasticsearch-users.115913.n3.nabble.com/Facets-Refactor-tp4030599p4030715.html
        //     scope (ou _scope) : deprecated
        //     cache_filter (ou cacheFilter)
        //     nested
        //
        // type=terms     (cf https://github.com/elasticsearch/elasticsearch/blob/master/src/main/java/org/elasticsearch/search/facet/terms/TermsFacetParser.java)
        //     params  : object =  ??
        //     exclude : array  = une liste de termes à exclure
        //     fields  : array  = liste des champs sur lesquels se fait le calcul
        //     field   : string = comme field, sur un seul champ
        //     script_field
        //     size
        //     all_terms
        //     regex
        //     regex_flags
        //     order
        //     script
        //     lang
        //     execution_hint
        //     _index
        //
        // Champs dont j'ai besoin :
        //    nom de code de la facette (ce qui sera mis en query string sous le forme facet.nom.facet
        //    Libellé à afficher pour la facette
        //    type de la facette (terms par défaut ?)
        //    état initial : normal (par défaut), collapsed, closed, hidden, disabled
        //    capacity ?
        //    Mode d'affichage de la facette : liste, liste sans count, tag cloud, etc.

        return array(
            'ref.type' => array(
                'label' => __('Type de document', 'docalist-biblio'),
                'facet' => array(
                    'field' => 'type.keyword',
//                    'order' => 'term',
                )
            ),
            'ref.topic' => array(
                'label' => __('Mot-clé', 'docalist-biblio'),
                'facet' => array(
                    'field' => 'topic.term.keyword',
                    'size'  => 10,
//                    'order' => 'term',
                )
            ),
            'ref.media' => array(
                'label' => __('Support de document', 'docalist-biblio'),
                'facet' => array(
                    'field' => 'media.keyword',
                )
            ),
            'ref.journal' => array(
                'label' => __('Revue', 'docalist-biblio'),
                'facet' => array(
                    'field' => 'journal.keyword',
                )
            ),
            'ref.author' => array(
                'label' => __('Auteur', 'docalist-biblio'),
                'facet' => array(
                    'field' => 'author.keyword',
                    'exclude' => array('et al.'),
                )
            ),
            'ref.genre' => array(
                'label' => __('Producteur de la notice', 'docalist-biblio'),
                'facet' => array(
                    'field' => 'owner.keyword',
                )
            ),
            'ref.owner' => array(
                'label' => __('Genre de document', 'docalist-biblio'),
                'facet' => array(
                    'field' => 'genre.keyword',
                )
            ),
            'ref.errors' => array(
                //'state' => 'closed',
                'label' => __('Erreurs détectées', 'docalist-biblio'),
                'facet' => array(
                    'field' => 'errors.code',
                )
            ),
        );
    }
}