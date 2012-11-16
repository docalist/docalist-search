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
use \Exception;

/**
 * Gestionnaire de plugins Docalist.
 */
class PluginManager {
    /**
     * Liste des plugins chargés.
     *
     * @var array() Les clés du tableau contiennent le path du répertoire du
     * plugin (tel que passé à {@link load()} et les valeurs contiennent
     * l'instance en cours du plugin.
     */
    public static $plugins = array();

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
        // Vérifie que ce plugin n'est pas déjà chargé
        if (isset(self::$plugins[$directory])) {
            $message = __('Plugin %s is already loaded.', 'docalist-core');
            throw new Exception(sprintf($message, $directory));
        }

        // Stocke le répertoire pour que l'autoload trouve la classe
        self::$plugins[$directory] = null;

        // Détermine le nom complet de la classe et vérifie que c'est un plugin
        //$class = 'Docalist\\' . $class;
        if (!class_exists($class)) {
            $message = __('Plugin class %s not found.', 'docalist-core');
            throw new Exception(sprintf($message, $class, 'Docalist\\Plugin'));
        }
        $baseClass = 'Docalist\\Core\\AbstractPlugin';
        if (!is_subclass_of($class, $baseClass)) {
            $message = __('Class %s must inherit from %s.', 'docalist-core');
            throw new Exception(sprintf($message, $class, $baseClass));
        }

        // Instancie le plugin demandé
        self::$plugins[$directory] = new $class($directory);
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
    public static function setupAutoloader() {
        self::$plugins[dirname(__DIR__)] = null;

        spl_autoload_register(function($class) {
            // Si ce n'est pas une classe Docalist, on ne s'en occupe pas
            if (strncmp($class, 'Docalist', 8) !== 0)
                return;

            // Détermine le path relatif du fichier contenant la classe
            $class = substr_replace($class, 'class', 0, 8) . '.php';

            // Met le séparateur correct dans le path
            $class = strtr($class, '\\', DIRECTORY_SEPARATOR);

            // Teste tous les plugins et essaie de charger le fichier
            foreach (PluginManager::$plugins as $directory => $plugin) {
                $path = $directory . DIRECTORY_SEPARATOR . $class;
                if (file_exists($path)) {
                    require_once $path;

                    return;
                }
            }

            // Aucun plugin n'a cette classe, laisse php signaler l'erreur
        });
    }


}
