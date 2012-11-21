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
use \Exception;

/**
 * Collection de méthodes utilitaires.
 */
class Utils {

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
