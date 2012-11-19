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
     * Préfixe utilisé en interne pour nommer dans Wordpress tous les objets
     * créés par les plugins Docalist (custom post types, taxonomies, custom
     * fields, etc.)
     */
    const PREFIX = 'dcl';

    /**
     * @var string Nom du plugin Docalist.
     *
     * Par convention, le nom d'un plugin Docalist correspond au deuxième
     * "étage" de son espace de noms (exemples : "Core", "Biblio", etc.)
     */
    protected $name;

    /**
     * @var string Chemin absolu du répertoire de base du plugin, sans slash
     * final.
     */
    protected $directory;

    /**
     * @var null|array Options des plugins docalist telles que retournées
     * par get_option('docalist-options').
     *
     * Cette propriété statique contient les options de tous les plugins
     * Docalist.
     *
     * Elle est initialisée par {@link option} lors du premier appel.
     */
    private static $options;

    /**
     * @var array Valeurs par défaut des options des plugins Docalist.
     *
     * Tous les plugins Docalist doivent déclarer les options de configuration
     * dont ils disposent en surchargeant la méthode{@link getDefaultOptions}.
     *
     * Les options retournées sont stockées, pour l'ensemble des plugins, dans
     * cette propriété statique.
     */
    private static $defaultOptions = array();

    /**
     * Initialise le plugin.
     * - Charge le domaine de texte du plugin pour gérer les traductions
     * - Définit et charge les options de configuration du plugin
     * - Met en place les taxonomies déclarées par le plugin
     * - Crée les custom post types du plugin
     * - Déclare dans Wordpress les pages d'administration spécifiques au
     *   plugin.
     * 
     * @param string $name nom du plugin.
     *
     * @param string $directory path complet du répertoire du plugin.
     */
    public function __construct($name, $directory) {
        $this->directory = $directory;
        $this->name = $name;

        // @formatter:off
        $this->setupTextDomain()
             ->setupOptions()
             ->setupTaxonomies()
             ->setupPostTypes()
             ->setupAdminPages();
        // @formatter:on
    }


    /**
     * Retourne le nom du plugin.
     *
     * Par convention, le nom d'un plugin Docalist correspond au deuxième
     * "étage" de son espace de noms. Par exemple, pour le plugin
     * "docalist-biblio", la classe principale du plugin s'appelle
     * "Docalist\Biblio\Plugin" et le nom du plugin est "Biblio".
     *
     * @return string
     */
    public function name() {
        return $this->name;
    }


    /**
     * Retourne le répertoire d'installation du plugin.
     *
     * @param boolean $relative Indique s'il faut retourner le chemin absolu
     * (valeur par défaut) ou bien le chemin relatif du plugin.
     *
     * @return string le path répertoire dans lequel est installé le plugin.
     * Retourne un chemin absolu si $relative est à faux et un chemin relatif
     * au répertoire "wp-content/plugins" de Wordpress sinon.
     */
    public function directory($relative = false) {
        return $relative ? basename($this->directory) : $this->directory;
    }


    /**
     * Retourne le domaine de texte à utiliser pour charger les fichiers
     * de traduction du plugin.
     *
     * Par convention, le domaine du plugin correspond au répertoire
     * de base du plugin (docalist-biblio, par exemple). Les plugins peuvent
     * néanmois surcharger cette méthode pour indiquer un domaine différent.
     *
     * (Exemple : {@link Plugin}).
     */
    public function textDomain() {
        return $this->directory(true);
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
        $path = $this->directory(true) . '/languages';

        load_plugin_textdomain($this->textDomain(), false, $path);

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
     * 
     * @todo Utiliser un FilesystemIterator.
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
     * @return Plugin $this
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


    /**
     * Crée dans Wordpress les pages d'administration définies par le plugin.
     *
     * La méthode crée une page pour chacun des fichiers .php présent dans
     * le répertoire /parts/admin-pages du plugin.
     *
     * @return Plugin $this;
     */
    protected function setupAdminPages() {
        add_action('piklist_admin_pages', array(
            $this,
            'piklistAdminPages'
        ));

        return $this;
    }


    /**
     * Fonction de callback interne utilisée par {@link setupAdminPages}.
     *
     * Remarque : dans une version précédente, cette méthode (qui ne devrait
     * pas être publique) était définie sous forme de closure dans
     * setupAdminPages, mais cela ne fonctionne qu'à partir de php 5.4
     * (utilisation de self et/ou $this dans le code de la closure).
     */
    public function piklistAdminPages($pages) {
        foreach (glob($this->directory . '/parts/admin-pages-def/*.php') as $file) {
            // @formatter:off
            $page = include ($file);
            //@formatter:on

            $pages[] = $page;
        }

        return $pages;
    }


    /**
     * Définit les options par défaut du plugin.
     *
     * Cette méthode initialise la propriété {@link $defaultOptions} en
     * y ajoutant les options retournées par {@link getDefaultOptions()}.
     *
     * @return Plugin $this
     */
    final protected function setupOptions() {
        // Récupère les options définies par ce plugin
        $options = $this->getDefaultOptions();

        // Merge avec les options générales
        if (!empty($options)) {
            self::$defaultOptions += $options;
        }

        return $this;
    }


    /**
     * Retourne les options par défaut définies par ce plugin.
     *
     * Les classes descendantes peuvent surcharger cette méthode pour définir
     * leurs propres options.
     *
     * Remarque : cette méthode est appellée juste après que le domaine de
     * texte du plugin ait été mis en place, ce qui fait que vous pouvez
     * utiliser les fonctions d'internationalisation de Wordpress (__, _e, etc.)
     * pour définir la valeur par défaut des options.
     *
     * @return null|array
     */
    protected function getDefaultOptions() {
        return null;
    }


    /**
     * Teste si l'option de configuration indiquée est valide.
     *
     * La méthode vérifie juste que l'option figure dans les valeurs par défaut
     * du plugin ({@link $defaultOptions}).
     *
     * Elle génère une erreur si ce n'est pas le cas.
     *
     * @param string $option le nom de l'option à tester.
     * @return boolean true si l'option existe, false sinon.
     */
    private function checkOption($option) {
        if (!array_key_exists($option, self::$defaultOptions)) {
            $message = __('Error in file <code>%s line %d</code>, option 
            <code>%s</code> is not defined in plugin <code>%s</code>.', 'docalist-core');

            $caller = next(debug_backtrace());
            $file = $caller['file'];
            $line = $caller['line'];
            $class = get_class($this);

            trigger_error(sprintf($message, $file, $line, $option, $class));

            return false;
        };

        return true;
    }


    /**
     * Retourne la valeur par défaut d'une option de configuration.
     *
     * En mode WP_DEBUG, une erreur est générée si l'option demandée est
     * inconnue.
     *
     * @param string $option le nom de l'option demandée.
     * @return mixed la valeur par défaut de l'option ou null si l'option
     * demandée n'existe pas.
     */
    public function defaultOption($option) {
        // Sanity check en mode debug
        WP_DEBUG && $this->checkOption($option);

        if (array_key_exists($option, self::$defaultOptions)) {
            return self::$defaultOptions[$option];
        }

        return null;
    }


    /**
     * Retourne la valeur d'une option de configuration.
     *
     * En mode WP_DEBUG, une erreur est générée si l'option demandée est
     * inconnue.
     *
     * @param string $option le nom de l'option demandée.
     * @return mixed le contenu de l'option.
     */
    public final function option($option) {
        // Sanity check en mode debug
        WP_DEBUG && $this->checkOption($option);

        // Si c'est le premier appel, charge les options depuis la base
        if (is_null(self::$options)) {
            self::$options = get_option('docalist-options');

            if (self::$options === false) {
                self::$options = array();
            }
        }

        // Teste si l'option a été modifiée par l'utilisateur
        if (array_key_exists($option, self::$options)) {
            return self::$options[$option];
        }

        // Retourne la valeur par défaut
        if (array_key_exists($option, self::$defaultOptions)) {
            return self::$defaultOptions[$option];
        }

        return null;
    }


}
