<?php
/**
 * This file is part of the "Docalist Core" plugin.
 *
 * Copyright (C) 2012 Daniel Ménard
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
use Exception;

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
     * @var string Nom du plugin.
     *
     * Par convention, le nom d'un plugin Docalist correspond au nom du fichier
     * principal du plugin (par exemple "docalist-core").
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
     * Cette propriété statique contient les options par défaut de tous les
     * plugins Docalist.
     *
     * Les plugins Docalist doivent déclarer les options de configuration
     * dont ils disposent en surchargeant la méthode{@link defaultOptions}.
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
     * @param string $name Nom du plugin.
     *
     * @param string $directory Chemin complet du répertoire de base du plugin.
     */
    public function __construct($name, $directory) {
        $this->directory = $directory;
        $this->name = $name;

        // @formatter:off
        $this->setupDomain()
             ->setupOptions()
             ->setupTaxonomies()
             ->setupPostTypes()
             ->setupAdminPages();
        // @formatter:on
        
        //register_deactivation_hook($file, $function);
        //register_activation_hook( $file, $function );
        //register_uninstall_hook($file, $callback);
    }


    /**
     * Retourne le nom du plugin.
     *
     * Par convention, le nom d'un plugin Docalist correspond au nom du fichier
     * principal du plugin (par exemple "docalist-core").
     * 
     * @return string
     */
    public function name() {
        return $this->name;
    }


    /**
     * Retourne le répertoire de base du plugin.
     *
     * @param boolean $relative Indique s'il faut retourner le chemin absolu
     * (valeur par défaut) ou bien un chemin relatif au répertoire plugins de
     * Wordpress.
     *
     * @return string Le path répertoire dans lequel est installé le plugin.
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
     * Par convention, le domaine du plugin correspond à son nom.
     * 
     * @return string
     */
    public function domain() {
        return $this->name;
    }


    /**
     * Charge les fichiers de traduction du plugin.
     *
     * Par convention, les fichiers po/mo du plugin sons stockés dans le
     * sous-répertoire 'languages'.
     *
     * @return AbstractPlugin $this
     */
    protected function setupDomain() {
        // Le path doit être relatif à WP_PLUGIN_DIR
        $path = $this->directory(true) . '/languages';

        load_plugin_textdomain($this->domain(), false, $path);

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
     * @return AbstractPlugin $this
     */
    protected function setupTaxonomies() {
        $mask = $this->directory . '/parts/taxonomies/*.php';
        $prefix = self::PREFIX;

        add_filter('piklist_taxonomies', function($taxonomies) use ($mask, $prefix) {
            foreach (glob($mask, GLOB_NOSORT) as $file) {
                // @formatter:off
                $taxonomy = include ($file);
                // @formatter:on

                $taxonomy['name'] = $prefix . $taxonomy['name'];

                foreach ($taxonomy['post_type'] as & $type) {
                    $type = $prefix . $type;
                }

                $taxonomies[] = $taxonomy;
            }

            return $taxonomies;

        });

        return $this;
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
     * @return AbstractPlugin $this
     */
    protected function setupPostTypes() {
        $mask = $this->directory . '/parts/post-types/*.php';
        $prefix = self::PREFIX;

        add_filter('piklist_post_types', function($types) use ($mask, $prefix) {
            foreach (glob($mask, GLOB_NOSORT) as $file) {
                // @formatter:off
                $type = include ($file);
                // @formatter:on

                $type['name'] = $prefix . $type['name'];

                $types[$type['name']] = $type;
            }

            return $types;
        });

        return $this;
    }


    /**
     * Crée dans Wordpress les pages d'administration définies par le plugin.
     *
     * Par défaut, la méthode ne fait rien mais les classes descendantes
     * peuvent la surcharger en cas de besoin (voir par exemple ce que fait
     * {@link Docalist\Core\Plugin::setupAdminPages()}).
     *
     * @return AbstractPlugin $this;
     */
    protected function setupAdminPages() {
        return $this;
    }


    /**
     * Définit les options par défaut du plugin.
     *
     * Cette méthode initialise la propriété {@link $defaultOptions} en
     * y ajoutant les options retournées par {@link defaultOptions()}.
     *
     * @return AbstractPlugin $this
     */
    protected function setupOptions() {
        // Récupère les options définies par ce plugin
        $options = $this->defaultOptions();

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
    protected function defaultOptions() {
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
            $msg = __('Option inconnue %s dans %s', 'docalist-core');
            throw new Exception(sprintf($msg, $option, Utils::caller()));

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
    public function option($option) {
        // Sanity check en mode debug
        WP_DEBUG && $this->checkOption($option);

        // Si c'est le premier appel, charge les options depuis la base
        if (is_null(self::$options)) {
            if (false === self::$options = get_option('docalist-options')) {
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


    /**
     * Retourne la liste des outils disponibles pour ce plugin.
     *
     * @return array un tableau d'objets de type
     * {@link Docalist\Core\AbstractTool}.
     */
    public function tools() {
        return array();
    }


}
