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

namespace Docalist;
use Docalist\Core\AbstractPlugin, Exception;

/**
 * Gestionnaire de plugins Docalist.
 */
final class PluginManager {
    /**
     * @var array Liste des plugins Docalist actuellement chargés.
     *
     * Les clés du tableau contiennent le nom du plugin (core, biblio, etc.)
     * et les valeurs contiennent l'instance.
     */
    protected static $plugins = array();

    /**
     * @var array Path du répertoire "class" des plugins Docalist actuellement
     * chargés.
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
    }


    /**
     * Charge un plugin et lance son exécution.
     *
     * @param string $class le nom de la classe à charger (le namespace
     * 'Docalist' est ajouté automatiquement).
     *
     * @param string $directory le path complet (absolu) du répertoire contenant
     * le code du plugin (typiquement : __DIR__).
     *
     * @throws Exception si le plugin indiqué est introuvable, s'il a déjà été
     * chargé ou si la classe indiquée n'hérite pas de la classe {@link Plugin}.
     */
    public static function load($class, $directory) {
        // Explose le nom de la classe (0 : docalist, 1 : name, 2 : plugin)
        $parts = explode('\\', $class, 3);

        // Debug : vérifie que la classe de base est dans le namespace Docalist
        if (WP_DEBUG && ($parts[0] !== __NAMESPACE__ || count($parts) !== 3)) {
            $message = __('Class %s must use Docalist namespace.', 'docalist-core');
            throw new Exception($message);
        }

        // Détermine le nom de code interne du plugin
        $name = $parts[1];

        // Vérifie que ce plugin n'est pas déjà chargé
        if (isset(self::$plugins[$name])) {
            $message = __('Plugin %s is already loaded.', 'docalist-core');
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
            $message = __('%s must inherit from %s.', 'docalist-core');
            throw new Exception(sprintf($message, $class, 'AbstractPlugin'));
        }
    }


    /**
     * Retourne l'instance d'un plugin actuellement chargé.
     *
     * @param string $name Le nom du plugin a retourner.
     *
     * @return Plugin
     *
     * @throws Exception Si le plugin demandé n'est pas chargé.
     */
    public function get($name) {
        if (!isset(self::$plugins[$name])) {
            $message = __('plugin not found %s', 'docalist-core');
            throw new Exception(sptrinf($message, $name));
        }

        return self::$plugins[$name];
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
            $path = self::$path[$plugin] . strtok('¤') . '.php';

            // Charge le fichier
            require_once $path;
        });
    }


}
