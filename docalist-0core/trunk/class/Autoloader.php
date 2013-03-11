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

namespace Docalist;
use Exception;

/**
 * Autoloader de Docalist.
 */
class Autoloader {
    /**
     * @var array Liste des namespaces enregistrés par register().
     *
     * Les clés du tableau contiennent le namespace, les valeurs contiennent
     * le path à utiliser pour charger les classes de ce namespace.
     */
    private static $path;

    /**
     * Enregistre un espace de noms dans l'autoloader.
     *
     * @param string $namespace Namespace à enregistrer (sensible à la casse).
     *
     * @param string $path Chemin absolu du dossier qui contient les classes
     * pour le namespace indiqué.
     */
    public static function register($namespace, $path) {
        // Premier appel
        if (is_null(self::$path)) {
            self::$path = array();

            // @formatter:off
            spl_autoload_register(array(__CLASS__, 'autoload'), true);
            // @formatter:on
        }

        // Vérifie que ce namespace n'a pas déjà été enregistré
        if (isset(self::$path[$namespace])) {
            $msg = __('Le namespace %s est déjà enregistré (%s).', 'docalist-core');
            throw new Exception(sprintf($msg, $namespace, self::$path[$namespace]));
        }

        // Enregistre le namespace
        $path = strtr($path, '/', DIRECTORY_SEPARATOR);
        self::$path = array($namespace => $path) + self::$path;

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
                    $msg = __('Erreur dans %s : fichier non trouvé %s (classe %s)', 'docalist-core');
                    throw new Exception(sprintf($msg, Utils::caller(), $path, $class));
                }

                // Charge le fichier
                require_once $path;

                // Vérifie que désormais la classe existe
                if (!class_exists($class, false) && ! interface_exists($class, false)) {
                    $msg = __('Erreur dans %s : classe %s inexistante', 'docalist-core');
                    throw new Exception(sprintf($msg, $path, $class));
                }

                // Ok
                return;
            }

            // Chargement en mode normal
            require_once $path;
            
            return;
        }
    }

}
