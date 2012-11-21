<?php
/**
 * This file is part of the "Docalist Core" plugin.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Core\Tools
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */

namespace Docalist\Core\Tools;
use Docalist, Docalist\Core\AbstractTool;

/**
 * Listes des outils disponibles.
 */
class ToolsList extends AbstractTool {
    /**
     * {@inheritdoc}
     */
    public function name() {
        return __('Outils Docalist', 'docalist-core');
    }


    /**
     * {@inheritdoc}
     */
    public function description() {
        return __('Affiche la liste des outils disponibles', 'docalist-core');
    }


    /**
     * Affiche la liste des outils disponibles.
     */
    public function actionIndex() {
        // Intro
        echo '<p>', 'Le tableau ci-dessous vous donne accès à tous les outils disponibles dans les plugins Docalist.', '</p>';

        // Début de table
        echo '<table class="widefat">';
        $alt = false;

        // Demande à chacun des plugins de donner sa liste d'outils
        $baseUrl = menu_page_url('docalist-tools', false);
        $ajaxUrl = admin_url('admin-ajax.php?action=docalist-tools');
        foreach (Docalist::plugins() as $name => $plugin) {
            // Affiche tous les outils de ce plugin
            foreach ($plugin->tools() as $tool) {

                // Début de ligne
                echo $alt ? '<tr class="alternate">' : '<tr>';

                // Affiche le nom de l'outil
                $name = $tool->name();
                $ajax = $tool->ajax();
                $url = $ajax ? $ajaxUrl : $baseUrl;
                $url .= '&t=' . Docalist::toolName($tool);
                $args = $tool->extraArguments();
                if ($args) {
                    $url .= '&' . trim($args, '&');
                }

                // @formatter:off
                printf (
                    '<td class="row-title"><a class="%s" href="%s" title="%s">%s</a></td>', 
                    $tool->ajax() ? 'thickbox' : '',
                    esc_attr($url), 
                    esc_attr($name), 
                    $name
                );
                // @formatter:on

                // Affiche la description de l'outil
                echo '<td class="desc">', $tool->description(), '</td>';

                // Fin de ligne
                echo '</tr>';
                $alt = !$alt;
            }
        }

        // Fin de la table
        echo '</table>';
    }


}
