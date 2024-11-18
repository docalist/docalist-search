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

use Docalist\Container\ContainerInterface;
use Docalist\Search\Indexer\PostIndexer;
use Docalist\Search\Indexer\PageIndexer;
use Docalist\Search\Widget\DisplayAggregations;
use Docalist\Search\Shortcodes;

/**
 * Plugin Docalist Search.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class DocalistSearchPlugin
{
    // /**
    //  * Les paramètres du moteur de recherche.
    //  *
    //  * @var Settings
    //  */
    // protected $settings;

    public function __construct(private ContainerInterface $container)
    {

    }

    public function initialize(): void
    {
        // Charge les fichiers de traduction du plugin
        load_plugin_textdomain('docalist-search', false, 'docalist-search/languages');

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
            $this->container->get(SettingsPage::class); // instanciation
        });

        // Déclare notre widget "Search Facets"
        add_action('widgets_init', function () {
            register_widget(__NAMESPACE__ . '\FacetsWidget'); // todo: vérifier et supprimer
            register_widget($this->container->get(DisplayAggregations::class));
        });

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
    protected function registerFacets(): void
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
        $request = services('docalist-search-engine')->getSearchRequest();

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
