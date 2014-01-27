<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2012-2014 Daniel Ménard
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
     * Le client utilisé pour communiquer avec le serveur ElasticSearch.
     *
     * @var ElasticSearchClient
     */
    protected $elasticSearchClient;

    /**
     * L'indexeur.
     *
     * @var Indexer
     */
    protected $indexer;

    /**
     * Le moteur de recherche.
     *
     * @var Searcher
     */
    protected $searcher;

    /**
     * {@inheritdoc}
     */
    public function __construct() {
        // Charge les fichiers de traduction du plugin
        load_plugin_textdomain('docalist-search', false, 'docalist-search/languages');

        // Charge la configuration du plugin
        $this->settings = new Settings('docalist-search');

        // Crée le service "elastic-search"
        docalist()->add('elastic-search', function() {
            return new ElasticSearchClient($this->settings->server);
        });

        add_filter('init', function() {

            // Enregistre les types de contenus indexables
            new PostIndexer();

            // Enregistre la liste des facettes disponibles
            $this->registerFacets();

            // Si la recherche est activée, initialise le moteur
            if ($this->settings->enabled) {
                $this->searcher = new Searcher($this->settings);
            }
        });

        // Déclare notre widget "Search Facets"
        add_action('widgets_init', function() {
            register_widget( __NAMESPACE__ . '\FacetsWidget' );
        });

        // Back office
        add_action('admin_menu', function() {
            // Indexer
            $this->indexer = new Indexer($this->settings);

            // Page des réglages
            new SettingsPage($this->settings, $this->indexer);
        });
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
            static $types;

            if ($facet !== '_type') {
                return  trim(str_replace('¤', ' ', $term));
            }

            if (is_null($types)) {
                $types = apply_filters('docalist_search_get_types', array());
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
        $request = $this->searcher->request();

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