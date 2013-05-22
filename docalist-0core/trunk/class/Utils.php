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
use Docalist\Forms\Assets;
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

    public static function enqueueAssets(Assets $assets) {
        foreach ($assets as $asset) {
            if (isset($asset['src']) && false === strpos($asset['src'], '//')) {
                //                $asset['src'] =
                // plugins_url('docalist-0core/lib/docalist-forms/'.$asset['src']);
                $asset['src'] = 'http://docalist-forms/src/' . $asset['src'];
            }

            // Fichiers JS
            if ($asset['type'] === Assets::JS) {
                wp_enqueue_script($asset['name'], $asset['src'], array(), $asset['version'], $asset['position'] === Assets::BOTTOM);
            }

            // Fichiers CSS
            else {
                wp_enqueue_style($asset['name'], $asset['src'], array(), $asset['version'], $asset['media']);
            }
        }
    }

    /**
     * Génère un UUID (Universally Unique IDentifiers) version 4 compatible
     * aved la RFC 4211.
     *
     * @return string
     *
     * @author Andrew Moore http://php.net/uniqid#94959
     */
    public function uuid() {
        //@formatter:off
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        //@formatter:on
    }

    /**
     * Génère les liens permettant d'accèder aux différentes pages de résultat.
     *
     */
    function pagesLinks($nb = 11, $first = false, $prev = true, $next = true, $last = false) {
        /**
         * @var WP_Query
         */
        global $wp_query;

        // Numéro de la page en cours
        $current = max(1, get_query_var('paged'));

        // Numéro de la première page à générer
        $firstPage = max(1, $current - intval($nb / 2));

        // Numéro de la dernière page à générer
        $lastPage = $firstPage + $nb - 1;

        // Ajustement des limites
        if ($lastPage > $wp_query->max_num_pages) {
            $lastPage = $wp_query->max_num_pages;
            $firstPage = max(1, $lastPage - $nb + 1);
        }

        $currentUrl = QueryString::fromCurrent();

        // Liens Début et Précédent
        if ($current > 1 && ($first || $prev)) {
            if ($first) {
                $url = $currentUrl->clear('page')->encode();
                $first === true && $first = 'Début';
                printf('<a class="page-numbers first" href="%s">%s</a> ', htmlspecialchars($url), $first);
            }
            if ($prev) {
                if ($current === 2) {
                    $url = $currentUrl->clear('page')->encode();
                } else {
                    $url = $currentUrl->set('page', $current - 1)->encode();
                }
                $prev === true && $prev = 'Précédent';
                printf('<a class="page-numbers previous" href="%s">%s</a> ', htmlspecialchars($url), $prev);
            }
        }

        // Liens 1 2 3 4 ...
        for ($link = $firstPage ; $link <= $lastPage ; $link++) {
            if ($link === $current) {
                printf('<span class="page-numbers current">%d</span> ', $link);
            } else {
                if ($link === 1) {
                    $url = $currentUrl->clear('page')->encode();
                } else {
                    $url = $currentUrl->set('page', $link)->encode();
                }

                printf('<a class="page-numbers" href="%s">%d</a> ', htmlspecialchars($url), $link);
            }
        }

        // Liens Suivant et Fin
        if ($current < $wp_query->max_num_pages && ($next || $last)) {
            if ($next) {
                $url = $currentUrl->set('page', $current + 1)->encode();
                $next === true && $next = 'Suivant';
                printf('<a class="page-numbers next" href="%s">%s</a> ', htmlspecialchars($url), $next);
            }
            if ($last) {
                $url = $currentUrl->set('page', $wp_query->max_num_pages)->encode();
                $last === true && $last = 'Fin';
                printf('<a class="page-numbers last" href="%s">%s</a> ', htmlspecialchars($url), $last);
            }
        }
    }
}
