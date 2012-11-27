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
        // Ajoute le préfixe Docalist à la taxonomie demandée
        $taxonomy = AbstractPlugin::PREFIX . $taxonomy;

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

}
