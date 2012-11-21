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

use Docalist\Core\AbstractPlugin, Docalist\Core\AbstractTool;

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
    protected static $plugins = array();

    /**
     * @var array Chemin vers le répertoire "class" des plugins Docalist
     * actuellement chargés.
     *
     * Les clés du tableau contiennent le nom du plugin (core, biblio, etc.)
     * et les valeurs contiennent le chemin d'accès absolu au répertoire "class"
     * du plugin, avec un slash final.
     */
    protected static $path = array();

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
     * @param string $directory le path complet (absolu) du répertoire contenant
     * le code du plugin (typiquement : __DIR__).
     *
     * @throws Exception si le plugin indiqué est introuvable, s'il a déjà été
     * chargé ou si la classe indiquée n'hérite pas de la classe
     * {@link AbstractPlugin}.
     */
    public static function load($class, $directory) {
        // Explose le nom de la classe (0 : docalist, 1 : name, 2 : plugin)
        $parts = explode('\\', $class, 3);

        // Debug : vérifie que la classe de base est dans le namespace Docalist
        if (WP_DEBUG && ($parts[0] !== 'Docalist' || count($parts) !== 3)) {
            $message = __('%s doit être dans le namespace Docalist.', 'docalist-core');
            throw new Exception(sprintf($message, $class));
        }

        // Détermine le namespace du plugin
        $name = $parts[1];

        // Vérifie que ce plugin n'est pas déjà chargé
        if (isset(self::$plugins[$name])) {
            $message = __('Le plugin %s est déjà chargé.', 'docalist-core');
            throw new Exception(sprintf($message, $name));
        }

        // Indique à l'autoloader le path des classes de ce plugin
        $classDir = $directory . '/class/' . $parts[1] . '/';
        self::$path[$name] = $classDir;

        // Optimisation : autoload inutile, on connaît le path exact
        require_once $classDir . $parts[2] . '.php';

        // Instancie le plugin demandé
        self::$plugins[$name] = new $class($name, $directory);

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
            throw new Exception(sptrinf($message, $name));
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
    protected static function setupAutoloader() {
        spl_autoload_register(function($class) {
            // Si ce n'est pas une classe Docalist, on ne s'en occupe pas
            if (strncmp($class, 'Docalist', 8) !== 0) {
                return;
            }

            // Détermine le path du fichier qui contient cette classe
            $class = substr($class, 9);
            $plugin = strtok($class, '\\');
            if (!isset(self::$path[$plugin])) {
                return;
            }
            $path = self::$path[$plugin] . strtok('¤') . '.php';

            // Aide au débogage
            if (WP_DEBUG && !file_exists($path)) {
                $msg = __('Classe inconnue <code>%s</code> dans <code>%s:%d</code>', 'docalist-core');
                $caller = next(debug_backtrace());
                $msg = sprintf($msg, $class, $caller['file'], $caller['line']);
                trigger_error($msg);
            }

            // Charge le fichier
            require_once $path;
        });
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
     * retourne la chaine "Core.Tools.ToolsList".
     *
     * @param AbstractTool $tool
     * @return string
     */
    public static function toolName(AbstractTool $tool) {
        return strtr(substr(get_class($tool), 9), '\\', '.');
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
        $name = 'Docalist\\' . strtr($name, '.', '\\');

        // Sanoty check
        if (WP_DEBUG && !is_subclass_of($name, 'Docalist\\Core\\AbstractTool')) {
            trigger_error('$name is not a subclass of AbstractTool');
            return;
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
    protected static function setupTools() {
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
