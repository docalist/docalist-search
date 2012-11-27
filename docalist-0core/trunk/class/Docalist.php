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

use Docalist\Core\AbstractPlugin, Docalist\Core\AbstractTool, Docalist\Core\Utils;

/**
 * Gestionnaire de plugins Docalist.
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
     * @var array Chemin vers le répertoire "class" des plugins Docalist
     * actuellement chargés.
     *
     * Les clés du tableau contiennent le nom du plugin (core, biblio, etc.)
     * et les valeurs contiennent le chemin d'accès absolu au répertoire "class"
     * du plugin, avec un slash final.
     */
    private static $path = array();

    /**
     * Initialise le gestionnaire de plugins.
     */
    public static function initialize() {
        self::setupAutoloader();
        self::setupTools();
    }


    /**
     * Charge un plugin et lance son exécution.
     *
     * @param string $class le nom complet de la classe principale du
     * plugin Docalist à charger.
     *
     * @param string $file le path complet (absolu) du fichier principal
     * du plugin (typiquement : __FILE__).
     *
     * @throws Exception si le plugin indiqué est introuvable, s'il a déjà été
     * chargé ou si la classe indiquée n'hérite pas de la classe
     * {@link AbstractPlugin}.
     */
    public static function load($class, $file) {

        // Détermine le nom du plugin et son répertoire de base
        $name = basename($file, '.php');
        $dir = dirname($file);

        // Vérifie que ce plugin n'est pas déjà chargé
        if (isset(self::$plugins[$name])) {
            $message = __('Le plugin %s est déjà chargé.', 'docalist-core');
            throw new Exception(sprintf($message, $name));
        }

        // Instancie le plugin demandé
        self::$plugins[$name] = new $class($name, $dir);

        // Debug : vérifie que c'est bien un plugin
        if (WP_DEBUG && !self::$plugins[$name] instanceof AbstractPlugin) {
            $message = __('%s doit hériter de %s.', 'docalist-core');
            throw new Exception(sprintf($message, $class, 'AbstractPlugin'));
        }
    }


    /**
     * Retourne l'instance d'un plugin actuellement chargé.
     *
     * @param string $name Le nom du plugin a retourner.
     *
     * @return AbstractPlugin
     *
     * @throws Exception Si le plugin demandé n'est pas chargé.
     */
    public function plugin($name) {
        if (!isset(self::$plugins[$name])) {
            $message = __('Plugin non trouvé : %s', 'docalist-core');
            throw new Exception(sprintf($message, $name));
        }

        return self::$plugins[$name];
    }


    /**
     * Retourne tous les plugins Docalist actuellement chargés.
     *
     * @return array un tableau de la forme nom=>instance
     */
    public function plugins() {
        return self::$plugins;
    }


    /**
     * Définit le répertoire que l'autoloader doit utiliser pour un namespace
     * donné.
     *
     * @param string $namespace Namespace à enregistrer (sensible à la casse).
     *
     * @param string $path Chemin absolu du dossier qui contient les classes
     * du namespace indiqué.
     */
    public static function registerNamespace($namespace, $path) {
        $path = strtr($path, '/', DIRECTORY_SEPARATOR);

        if (isset(self::$path[$namespace])) {
            $msg = __('Le namespace %s est déjà enregistré (%s).', 'docalist-core');
            throw new Exception(sprintf($msg, $namespace, self::$path[$namespace]));
        } else {
            self::$path[$namespace] = $path;
        }
    }


    /**
     * Met en place un autoloader qui charge automatiquement les classes
     * définies par les plugins Docalist lorsqu'on en a besoin.
     *
     * Toutes les classes sont stockées dans le répertoire "class" du plugin
     * et les noms des fichiers suivent la hiérarchie du namespace (PSR-0).
     *
     * Par exemple, une classe qui s'appellerait Docalist\Package\Group\Class
     * doit être stockée dans le fichier /class/Package/Group/class.php du
     * plugin.
     */
    private static function setupAutoloader() {
        // @formatter:off
        spl_autoload_register(array(__CLASS__, 'autoload'), true);
        // @formatter:on
    }


    /**
     * Autoloader. Cette fonction est appellée automatiquement par
     * spl_autoload_call lorsqu'une classe demandée n'existe pas.
     *
     * Notre auloader ne sait charger que les classes dont le namespace
     * a été enregistré dans {@link registerNamespace()}.
     *
     * En mode WP_DEBUG, des tests supplémentaires sont effectués et une
     * aide est affichée si la classe demandée ne figure pas là où elle
     * devrait.
     *
     * @param string $class Nom complet de la classe à charger.
     */
    private static function autoload($class) {
        // Cet autoloader ne sait pas charger des classes sans namespace
        if (false === strpos($class, '\\')) {
            return;
        }

        // Regarde si la classe figure dans un namespace qu'on connait
        foreach (self::$path as $namespace => $path) {

            // Teste si les namespaces correspondent
            if (strncmp($namespace, $class, strlen($namespace)) !== 0) {
                continue;
            }

            // Détermine le path du fichier
            $file = substr($class, strlen($namespace));
            $file = strtr($file, '\\', DIRECTORY_SEPARATOR);
            $path = $path . $file . '.php';

            // En mode Debug : on fait des vérifs supplémentaires
            if (WP_DEBUG) {
                // Vérifie que le fichier existe
                if (!is_file($path)) {
                    $msg = __('Erreur dans %s : classe inconnue "%s", impossible de charger %s', 'docalist-core');
                    throw new Exception(sprintf($msg, Utils::caller(), $class, $path));
                }

                // Charge le fichier
                require_once $path;

                // Vérifie que désormais la classe existe
                if (!class_exists($class, false) && ! interface_exists($class, false)) {
                    $msg = __('Erreur dans %s : classe inconnue "%s" non trouvée', 'docalist-core');
                    throw new Exception(sprintf($msg, $path, $class));
                }

                // Ok
                return;
            }

            // Chargement en mode normal
            require_once $path;
        }
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
     * @param AbstractTool $tool
     * @return string
     */
    public static function toolName(AbstractTool $tool) {
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
     * @return AbstractTool
     */
    public static function tool($name) {
        // Vérifie que c'est un outil Docalist
        $name = strtr($name, '.', '\\');

        // Sanoty check
        if (WP_DEBUG && !is_subclass_of($name, 'Docalist\\Core\\AbstractTool')) {
            $msg = __("%s n'est pas un outil Docalist", 'docalist-core');
            throw new Exception(sprintf($msg, $name));
        }

        // Retourne l'outil
        return new $name();
    }


    /**
     * Initialise Wordpress pour permettre l'exécution en ajax des outils
     * pour lesquelles la méthode {@link AbstractTool::ajax()} retourne vrai.
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
