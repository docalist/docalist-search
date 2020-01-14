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

use Docalist\Views;
use Docalist\Search\Indexer\PostIndexer;
use Docalist\Search\Indexer\PageIndexer;
use Docalist\Search\Lookup\IndexLookup;
use Docalist\Search\Lookup\SearchLookup;
use Docalist\Search\MappingBuilder\ElasticsearchMappingBuilder;
use Docalist\Search\QueryParser\Parser;
use Exception;
use Docalist\Services;
use Docalist\Search\Widget\DisplayAggregations;
use Docalist\Search\Shortcodes;

/**
 * Plugin Docalist Search.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Plugin
{
    /**
     * Les paramètres du moteur de recherche.
     *
     * @var Settings
     */
    protected $settings;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        // Charge les fichiers de traduction du plugin
        load_plugin_textdomain('docalist-search', false, 'docalist-search/languages');

        // Ajoute notre répertoire "views" au service "docalist-views"
        add_filter('docalist_service_views', function (Views $views) {
            return $views->addDirectory('docalist-search', DOCALIST_SEARCH_DIR . '/views');
        });

        // Charge la configuration du plugin
        $this->settings = new Settings(docalist('settings-repository'));

        // Services fournis pas ce plugin
        docalist('services')->add([
            'elasticsearch' => function () {
                return new ElasticSearchClient($this->settings);
            },
            'elastic-search' => function () {
                _deprecated_hook('Le service "elastic-search"', '0.12', 'le service "elasticsearch"', '(sans tiret)');
                return docalist('elasticsearch');
            },
            'elasticsearch-query-dsl' => function () {
                $version = $this->settings->esversion();
                if ($version >= '5.0.0') {
                    return new QueryDSL\Version500();
                } elseif ($version >= '2.0.0') {
                    return new QueryDSL\Version200();
                } else {
                    return new QueryDSL\Version200();
                }
            },
            'elasticsearch-version' => function () {
                $version = $this->settings->esversion();
                if (is_null($version) || $version === '0.0.0') {
                    throw new Exception('Elasticsearch version is not available, check settings.');
                }
                return $version;
            },
            'mapping-builder' => function (Services $services) {
                return new ElasticsearchMappingBuilder($services->get('elasticsearch-version'));
            },
            'docalist-search-index-manager' => new IndexManager($this->settings),
            'docalist-search-engine' => new SearchEngine($this->settings),

            'docalist-search-attributes' => function (Services $services) {
                $index = $services->get('docalist-search-index-manager'); /** @var IndexManager $index */

                return $index->getSearchAttributes();
            },

            'index-lookup' => function (Services $services) {
                return new IndexLookup($services->get('elasticsearch-version'));
            },

            'search-lookup' => function () {
                return new SearchLookup();
            },

            'query-parser' => function () {
                return new Parser($this->settings->getDefaultSearchFields());
            },
        ]);

        // Liste des indexeurs prédéfinis
        add_filter('docalist_search_get_indexers', function ($indexers) {
            $indexers['post'] = new PostIndexer();
            $indexers['page'] = new PageIndexer();

            return $indexers;
        });

        // Enregistre la liste des facettes disponibles
        $this->registerFacets();

        // Crée la page Réglages » Docalist Search
        add_action('admin_menu', function () {
            new SettingsPage($this->settings);
        });

        // Déclare notre widget "Search Facets"
        add_action('widgets_init', function () {
            register_widget(__NAMESPACE__ . '\FacetsWidget');
            register_widget(DisplayAggregations::class);
        });

        // Définit les lookups de type "index"
//         add_filter('docalist_index_lookup', function ($value, $source, $search) {
//             return docalist('docalist-search-engine')->termLookup($source, $search);
//         }, 10, 3);

        // Enregistre les shortcodes
        $shortcodes = new Shortcodes();
        $shortcodes->register();
    }

    /**
     * Retourne le numéro de version du plugin.
     *
     * @return string
     */
    public function getVersion()
    {
        static $version = null;

        if (is_null($version)) {
            $version = get_plugin_data(__DIR__ . '/../docalist-search.php', false, false)['Version'];
        }

        return $version;
    }

    /**
     * Enregistre la liste des facettes disponibles.
     *
     * Pour le moment, une seule : type de contenu (_type).
     */
    protected function registerFacets()
    {
        add_filter('docalist_search_get_facets', function ($facets) {
            $facets += [
                '_type' => [
                    'label' => __('Type de contenu', 'docalist-search'),
                    'facet' => [
                        'field' => '_type',
                        // 'order' => 'term',
                    ],
                ],
            ];

            return $facets;
        });

        add_filter('docalist_search_get_facet_label', function ($term, $facet) {
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
     * - %2$s : la valeur/le libellé du filtre
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
    public function theCurrentFilters($format = null, $separator = null, $wrapper = null)
    {
        /* @var SearchRequest $request */
/*
        $request = docalist('docalist-search-engine')->getSearchRequest();

        // Retourne une chaine vide si on n'a aucun filtre actif
        $request && $filters = $request->filters();
        if (empty($filters)) {
            return '';
        }

        // Format par défaut
        is_null($format) && $format = '<a href="%3$s" class="%4$s">%2$s</a>';

        // Génère la liste des filtres
        $result = [];
        foreach ($filters as $filter => $values) {
            $class = 'filter-' . strtr($filter, '.', '-');
            foreach (array_keys($values) as $value) {
                $url = $request->toggleFilterUrl($filter, $value);
                $value = apply_filters('docalist_search_get_facet_label', $value, $filter);
                $result[] = sprintf($format, esc_html($filter), esc_html($value), esc_url($url), esc_attr($class));
            }
        }

        // Wrapper par défaut
        if (is_null($wrapper)) {
            $label = _n('Filtre : %s', 'Filtres : %s', count($result), 'docalist-biblio');
            $wrapper = sprintf('<p class="current-search-filters">%s.</p>', $label);
        }

        $result = implode(is_null($separator) ? ', ' : $separator, $result);

        return sprintf($wrapper, $result);
*/
        return 'TODO / theCurrentFilters';
    }
}
