<?php
/**
 * This file is part of the "Docalist Core" plugin.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */

namespace Docalist\Core;

/**
 * Classe de base abstraite représentant un plugin Docalist.
 */
abstract class AbstractPlugin {
    /**
     * Préfixe utilisé en interne pour nommer les objets créés par les plugins
     * docalist (custom post types, taxonomies, custom fields, ...)
     *
     * @return string
     */
    const PREFIX = 'dcl';

    /**
     * @var string Nom de base du plugin (nom du dossier).
     */
    protected $baseName;

    /**
     * @var string le path complet du répertoire qui contient le code du plugin
     * (sans slash final).
     */
    protected $directory;

    /**
     * Initialise le plugin.
     */
    public function __construct($directory) {
        $this->directory = $directory;
        $this->baseName = basename($directory);

        $this->setupTextDomain()->setupTaxonomies()->setupPostTypes();

        add_action('piklist_admin_pages', array(
            __CLASS__,
            'registerPages'
        ));
    }


    /**
     * Retourne le nom de base du plugin.
     *
     * Cette méthode est une version simplifiée de la fonction
     * plugin_basename() de Wordpress.
     *
     * @return string le nom du répertoire dans lequel est installé le plugin.
     */
    public function pluginBasename() {
        return $this->baseName;
    }


    /**
     * Retourne le répertoire de base du plugin.
     *
     * Similaire à la fonction plugin_dir_path() de Wordpress mais sans slash
     * inutile à la fin.
     *
     * @return string le path absolu du répertoire dans lequel est installé le
     * plugin.
     */
    public function directory() {
        return $this->directory;
    }


    /**
     * Charge les fichiers de traduction du plugin.
     *
     * Par convention, les fichiers po/mo du plugin sons stockés dans le
     * sous-répertoire 'languages'.
     *
     * @return Plugin $this
     */
    protected function setupTextDomain() {
        // le path doit être relatif à WP_PLUGIN_DIR
        $path = $this->baseName . '/languages';

        load_plugin_textdomain($this->baseName, false, $path);

        return $this;
    }


    /**
     * Crée dans Wordpress les taxonomies définies par le plugin.
     *
     * La méthode crée une taxonomie pour chacun des fichiers .php présent dans
     * le répertoire /parts/taxonomies du plugin.
     *
     * Les taxonomies créées, ainsi que les types de contenus auxquels elles
     * sont rattachées sont préfixées avec la chaine indiquée dans la constante
     * {@link PREFIX}.
     *
     * @return Plugin $this
     */
    protected function setupTaxonomies() {
        add_filter('piklist_taxonomies', array(
            $this,
            'piklistTaxonomies'
        ));

        return $this;
    }


    /**
     * Fonction de callback interne utilisée par {@link setupTaxonomies}.
     *
     * Remarque : dans une version précédente, cette méthode (qui ne devrait
     * pas être publique) était définie sous forme de closure dans
     * setupTaxonomies, mais cela ne fonctionne qu'à partir de php 5.4
     * (utilisation de self et/ou $this dans le code de la closure).
     */
    public function piklistTaxonomies($taxonomies) {
        foreach (glob($this->directory . '/parts/taxonomies/*.php') as $file) {
            // @formatter:off
            $taxonomy = include ($file);
            //@formatter:on

            $taxonomy['name'] = self::PREFIX . $taxonomy['name'];

            foreach ($taxonomy['post_type'] as & $post_type) {
                $post_type = self::PREFIX . $post_type;
            }

            $taxonomies[] = $taxonomy;
        }

        return $taxonomies;
    }


    /**
     * Crée dans Wordpress les custom post types définis par le plugin.
     *
     * La méthode crée un CPT pour chacun des fichiers .php présent dans
     * le répertoire /parts/post-types du plugin.
     *
     * Les CPT créés sont préfixés avec la chaine indiquée dans la constante
     * {@link PREFIX}.
     *
     * @return $this;
     */
    protected function setupPostTypes() {
        add_filter('piklist_post_types', array(
            $this,
            'piklistPostTypes'
        ));

        return $this;
    }


    /**
     * Fonction de callback interne utilisée par {@link setupPostTypes}.
     *
     * Remarque : dans une version précédente, cette méthode (qui ne devrait
     * pas être publique) était définie sous forme de closure dans
     * setupPostTypes, mais cela ne fonctionne qu'à partir de php 5.4
     * (utilisation de self et/ou $this dans le code de la closure).
     */
    public function piklistPostTypes($post_types) {
        foreach (glob($this->directory . '/parts/post-types/*.php') as $file) {
            // @formatter:off
            $type = include ($file);
            //@formatter:on

            $type['name'] = self::PREFIX . $type['name'];

            $post_types[$type['name']] = $type;
        }

        return $post_types;

    }


    public static function registerPages($pages) {
        $pages[] = array(
            'page_title' => __('Tools', 'docapress'), // Title of page
            'menu_title' => __('Tools', 'docapress'), // Title of menu link
            'sub_menu' => 'edit.php?post_type=docapress_records', // Show this
            // page under the THEMES menu
            'capability' => 'manage_options', // Minimum capability to see this
            // page
            'menu_slug' => 'docapress-tools', // Menu slug
            // 'setting'    => 'docapress-tools',        // The settings name
            'icon' => 'options-general' // Menu/Page Icon
        );

        $pages[] = array(
            'page_title' => __('Docapresss options', 'docapress'), // Title of
            // page
            'menu_title' => __('Options', 'docapress'), // Title of menu link
            'sub_menu' => 'edit.php?post_type=docapress_records', // Show this
            // page under the THEMES menu
            'capability' => 'manage_options', // Minimum capability to see this
            // page
            'menu_slug' => 'docapress-options', // Menu slug
            'setting' => 'docapress-options', // The settings name
            'icon' => 'tools', // Menu/Page Icon
            'default_tab' => 'General'
        );

        return $pages;
    }


}
