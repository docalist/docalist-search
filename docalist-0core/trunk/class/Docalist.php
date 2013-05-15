<?php
/**
 * This file is part of the "Docalist Core" plugin.
 *
 * Copyright (C) 2012, 2013 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */

use Docalist\Plugin, Docalist\Tool, Docalist\Utils;

/**
 * Gestionnaire de plugins Docalist.
 *
 * Cette classe est essentiellement un Container : elle sait charger un plugin
 * et gère la liste des plugins chargés.
 */
final class Docalist {
    /**
     * @var array Liste des plugins Docalist actuellement chargés.
     *
     * Les clés du tableau contiennent le nom du plugin (core, biblio, etc.)
     * et les valeurs contiennent l'instance.
     */
    private static $plugins = array();

    /**
     * Charge un plugin Docalist.
     *
     * @param string $class le nom complet de la classe à charger.
     *
     * @param string $file le path complet du fichier principal du plugin
     * (typiquement : __FILE__).
     *
     * @return Plugin le plugin.
     *
     * @throws Exception si le plugin indiqué est introuvable, s'il a déjà été
     * chargé ou si la classe indiquée n'hérite pas de la classe {@link Plugin}.
     */
    public static function load($class, $file) {
        // Détermine l'identifiant du plugin et son répertoire de base
        $id = basename($file, '.php');
        $directory = dirname($file);

        // Sanity check en mode debug
        if (WP_DEBUG) {
            // Vérifie que la classe existe et que c'est bien un plugin
            Utils::checkClass($class, 'Docalist\Plugin');

            // Vérifie que le plugin n'est pas déjà chargé
            if (isset(self::$plugins[$id])) {
                $message = __('Le plugin %s est déjà chargé.', 'docalist-core');
                throw new Exception(sprintf($message, $id));
            }
        }

        // Instancie le plugin demandé
        $plugin = new $class($id, $directory);
        self::$plugins[$id] = $plugin;

        // Appelle la méthode register() lors de l'action init() de WordPress
        add_action('init', array(
            $plugin,
            'register'
        ), 1, 999);
    }

    /**
     * Retourne le plugin dont l'identifiant est passé en paramètre.
     *
     * @param string $id L'identifiant du plugin a retourner.
     *
     * Si aucun paramètre n'est indiqué, get() retourne un tableau contenant
     * tous les plugins docalist actuellement chargés.
     *
     * @return Plugin|Plugin[]
     *
     * @throws Exception Si le plugin demandé n'est pas chargé.
     */
    public static function get($id = null) {
        if (!$id) {
            return self::$plugins;
        }

        if (!isset(self::$plugins[$id])) {
            $message = __('Plugin non trouvé : %s', 'docalist-core');
            throw new Exception(sprintf($message, $id));
        }

        return self::$plugins[$id];
    }

    /**
     * Retourne le nom de code d'un outil, tel qu'il est passé dans le
     * paramètre "t" de la query string lorsque l'outil est exécuté
     * (cf. {@link Docalist\Core\Tools\ToolsList::actionIndex()}).
     *
     * La méthode fonctionne en convertissant le nom complet de la classe
     * de l'outil passé en paramètre.
     *
     * Par exemple, pour la classe {@link Docalist\Core\Tools\ToolsList}, elle
     * retourne la chaine "Docalist.Core.Tools.ToolsList".
     *
     * @param Tool $tool
     * @return string
     */
    public static function toolName(Tool $tool) {
        return strtr(get_class($tool), '\\', '.');
    }

    /**
     * Tool Factory : crée une instance de l'outil dont le nom de code est
     * passé en paramètre.
     *
     * @param string $name le nom de code de l'outil, tel que retourné par la
     * méthode {@link toolName()}.
     *
     * Exemple : <code>Docalist::tool('Core.Tools.ToolsList')</code>
     *
     * @return Tool
     */
    public static function tool($name) {
        // Vérifie que c'est un outil Docalist
        $name = strtr($name, '.', '\\');

        // Sanoty check
        if (WP_DEBUG && !is_subclass_of($name, 'Docalist\Tool')) {
            $msg = __("%s n'est pas un outil Docalist", 'docalist-core');
            throw new Exception(sprintf($msg, $name));
        }

        // Retourne l'outil
        return new $name();
    }

    /**
     * Initialise Wordpress pour permettre l'exécution en ajax des outils
     * pour lesquelles la méthode {@link Tool::ajax()} retourne vrai.
     *
     * Cette méthode ne fait rien si la requête en cours n'est pas une requête
     * ajax (i.e. si la constante Wordpress DOING_AJAX n'est pas définie).
     *
     * Dans le cas contraire, elle installe deux hooks Wordpress
     * (wp_ajax_docalist-tools et wp_ajax_nopriv_docalist-tools) qui se
     * chargent de lancer l'exécution de l'outil lorsque le fichier
     * Wordpress/admin-ajax.php est appellé.
     */
    private static function setupTools() {
        // On ne fait rien si ce n'est pas une requête ajax
        if (!defined('DOING_AJAX')) {
            return;
        }

        // Le hook détermine l'outil, le charge et le lance
        $hook = function() {
            // L'outil doit $etre indiqué dans la clé "t" de la query string
            if (!isset($_REQUEST['t']) || !$tool = $_REQUEST['t']) {
                if (WP_DEBUG) {
                    echo "400 Bad ajax request : t is required";
                }
                return;
            }

            // Lance l'exécution de l'outil
            self::tool($tool)->run();

            // Par défaut, admin-ajax.php génère un die('0')
            exit(0);
            // exit status 0 = success

        };

        // Installe les hooks
        add_action('wp_ajax_docalist-tools', $hook);
        add_action('wp_ajax_nopriv_docalist-tools', $hook);
    }

}
