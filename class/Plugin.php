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
 * @version     SVN: $Id$
 */
namespace Docalist\Search;
use Docalist\QueryString;

/* Documentation : doc/search-design.md */

/**
 * Plugin Docalist Search.
 */
class Plugin {

    /**
     * Les paramètres du moteur de recherche.
     *
     * @var Settings
     */
    protected $settings;

    /**
     * {@inheritdoc}
     */
    public function __construct() {
        // Charge les fichiers de traduction du plugin
        load_plugin_textdomain('docalist-search', false, 'docalist-search/languages');

        // Charge la configuration du plugin
        $this->settings = new Settings(docalist('settings-repository'));

        // Enregistre nos services
        docalist('services')->add([

            // Service "elastic-search"
            'elastic-search' => function() {
                return new ElasticSearchClient($this->settings->server);
            },

            // Service "docalist-search-indexer"
            'docalist-search-indexer' => new Indexer($this->settings->indexer),

            // Service "docalist-search-engine"
            'docalist-search-engine' =>  new SearchEngine($this->settings)
        ]);

        // Retourne les settings par défaut à utiliser quand un index est créé
        // Remarque : priorité 1 pour être le premier
        add_filter('docalist_search_get_index_settings', function (array $settings) {
            return require __DIR__ . '/../index-settings/default.php';
        }, 1);

        // Retourne les types de contenus indexables
        add_filter('docalist_search_get_types', function (array $types) {
            $types['post'] = get_post_type_object('post')->labels->name;
            $types['page'] = get_post_type_object('page')->labels->name;

            return $types;
        });

        // Retourne l'indexeur à utiliser pour les articles
        add_filter('docalist_search_get_post_indexer', function(TypeIndexer $indexer = null) {
            is_null($indexer) && $indexer = new PostIndexer();

            return $indexer;
        });

        // Retourne l'indexeur à utiliser pour les pages
        add_filter('docalist_search_get_page_indexer', function(TypeIndexer $indexer = null) {
            is_null($indexer) && $indexer = new PageIndexer();

            return $indexer;
        });

        // Enregistre la liste des facettes disponibles
        $this->registerFacets();

        // Crée la page Réglages » Docalist Search
        add_action('admin_menu', function() {
            new SettingsPage($this->settings);
        });

        // Déclare notre widget "Search Facets"
        add_action('widgets_init', function() {
            register_widget( __NAMESPACE__ . '\FacetsWidget' );
        });

        // Définit les lookups de type "index"
        add_filter('docalist_index_lookup', function($value, $source, $search) {
            return docalist('docalist-search-engine')->lookup($source, $search);
        }, 10, 3);
    }

    /**
     * Retourne le numéro de version du plugin.
     *
     * @return string
     */
    public function version() {
        return get_plugin_data(__DIR__ . '/../docalist-search.php', false, false)['Version'];
    }

    /**
     * Enregistre la liste des facettes disponibles.
     *
     * Pour le moment, une seule : type de contenu (_type).
     */
    protected function registerFacets() {
        add_filter('docalist_search_get_facets', function($facets) {
            $facets += array(
                '_type' => array(
                    'label' => __('Type de contenu', 'docalist-search'),
                    'facet' => array(
                        'field' => '_type',
                        // 'order' => 'term',
                    )
                ),
            );

            return $facets;
        });

        add_filter('docalist_search_get_facet_label', function($term, $facet) {
            static $types = null;

            if ($facet !== '_type') {
                return  trim(str_replace('¤', ' ', $term));
            }

            if (is_null($types)) {
                $types = apply_filters('docalist_search_get_types', []);
            }

            isset($types[$term]) && $term = $types[$term];

            return $term;
        }, 10, 2);
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
        // $request = $this->searchengine->request();
        $request = docalist('docalist-search-engine')->request();

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

        // Génère la liste des filtres
        $currentUrl = QueryString::fromCurrent();
        $result = '';
        $nb = 0;
        foreach($filters as $filter => $values) {
            $class = 'filter-' . strtr($filter, '.', '-');
            foreach ($values as $value) {
                $url = $currentUrl->copy()->clear($filter, $value)->encode();
                $nb++ && $result .= $separator;
                $value = apply_filters('docalist_search_get_facet_label', $value, $filter);
                $result .= sprintf($format, $filter, $value, $url, $class);
            }
        }

        // Wrapper par défaut
        if (is_null($wrapper)) {
            $label = _n('Filtre : %s', 'Filtres : %s', $nb, 'docalist-biblio');
            $wrapper = sprintf('<p class="current-search-filters">%s.</p>', $label);
        }

        return sprintf($wrapper, $result);
    }

}