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
 * Collection de méthodes utilitaires.
 */
class Utils {
    /**
     * Indique qui est l'appellant d'une fonction ou d'une méthode.
     *
     * Retourne une chaine qui indique le fichier et le numéro de ligne
     * où a été appellé la méthode.
     *
     * @return string une chaine de la forme path:line (ou path seulement
     * si le numéro de ligne n'est pas connu).
     */
    public static function caller() {
        // Récupère la pile des appels (on optimise si php >= 5.4.0)
        if (PHP_VERSION_ID < 50400) {
            $trace = debug_backtrace();
        } else {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        }

        // Détermine l'appellant (0=nous, 1=notre appellant, 2=ce qu'on veut)
        $caller = $trace[2];

        // Dans certains cas, on peut ne pas avoir file
        if (!isset($caller['file'])) {
            if (isset($caller['function'])) {
                return $caller['function'] . '()';
            }
            return '';
        }

        // Détermine le path du fichier et essaie de le mettre en relatif
        $file = $caller['file'];

        // Dans certains cas (closures), line n'est pas défini
        if (!isset($caller['line'])) {
            return $file;
        }

        // Retourne le nom du fichier et le numéro de ligne
        return $file . ':' . $caller['line'];
    }

    /**
     * Dumpe les arguments passés en paramètre en les encadrant d'une balise
     * <pre>.
     *
     * @param mixed $args un ou plusieurs arguments à dumper.
     */
    function pre($args) {
        echo '<pre>';
        foreach (func_get_args() as $arg) {
            var_export($arg);
        }
        echo '</pre>';
    }

    /**
     * Retourne un tableau contenant tous les termes de la taxonomie indiquée
     * et ajoute éventuellement une entrée vide.
     *
     * Cette fonction sert à peupler les select générés par Piklist.
     *
     * @return array() un tableau de la forme slug => label
     */
    function choices($taxonomy, $addEmpty = false) {
        // Vérifie que cette taxonomie existe
        if (!taxonomy_exists($taxonomy)) {
            $message = __('Taxonomie inexistante : %s.', 'docalist-core');
            throw new Exception(sprintf($message, $taxonomy));
        }

        // Charge tous les termes
        $terms = get_terms($taxonomy, array('hide_empty' => false));

        // Ne conserve que le slug et le nom
        $terms = piklist($terms, array(
            'slug',
            'name'
        ));

        // Ajoute en premier une option vide
        if ($addEmpty !== false) {
            $terms = array('' => $addEmpty === true ? '' : $addEmpty) + $terms;
        }

        return $terms;
    }

    /**
     * Retourne le namespace de la classe indiquée
     *
     * @return string
     */
    public function ns($class) {
        if (is_object($class))
            $class = get_class($class);

        return substr($class, 0, strrpos($class, '\\'));
    }

    /**
     * Retourne le nom de base de la classe indiquée (sans le namespace)
     *
     * @return string
     */
    public function classname($class) {
        if (is_object($class))
            $class = get_class($class);

        return substr($class, strrpos($class, '\\') + 1);
    }

    /**
     * Vérifie que l'objet (ou la classe) passée en paramètre hérite d'une
     * classe donnée et génère une exception si ce n'est pas le cas.
     *
     * @param string|object $object la classe ou l'objet à tester.
     * @param string $class la classe attendue
     *
     * @throws Exception si $object n'est pas une sous-classe de $class.
     */
    public function checkClass($object, $class) {
        if (!is_subclass_of($object, $class)) {
            $message = __('La classe %s doit hériter de la classe %s.', 'docalist-core');
            $bad = is_string($object) ? $object : get_class($object);
            throw new Exception(sprintf($message, $bad, $class));
        }
    }

    /**
     * Implémentation de la méthode get() pour un container.
     */
    public static function containerGet(Container $container, array & $items, $name){
    // TraitContainer : supprimer cette méthode
        if (! isset($items[$name])) {
            $msg = __('Aucun objet %s dans ce container', 'docalist-core');
            throw new Exception(sprintf($msg, $name));
        }

        return $items[$name];
    }

    /**
     * Implémentation de la méthode add() pour un container.
     */
    public static function containerAdd(Container $container, array & $items, Registrable $object){
    // TraitContainer : supprimer cette méthode
        $name = $object->name();
        if (isset($items[$name])) {
            $msg = __('Il existe déjà un objet %s dans ce container', 'docalist-core');
            throw new Exception(sprintf($msg, $name));
        }

        // Ajoute l'objet dans la collection
        $object->parent($container);
        $items[$name] = $object;

        // Enregistre l'objet
        $object->register();

        return $container;
    }


}
