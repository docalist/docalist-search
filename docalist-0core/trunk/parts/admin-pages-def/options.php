<?php
/**
 * This file is part of a "Docalist Biblio" plugin.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Biblio
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */

namespace Docalist;

/**
 * Crée la page "Options" de Docalist.
 * 
 * Références :
 * - @link http://codex.wordpress.org/Function_Reference/add_menu_page
 * - @link http://codex.wordpress.org/Function_Reference/add_submenu_page
 */
return array(
    // Titre de la page
    'page_title' => _x('Docalist settings', 'Settings page title', 'docalist-core'),

    // Libellé du menu
    'menu_title' => _x('Docalist', 'Settings page menu label', 'docalist-core'),

    // Nom du menu auquel va être rattachée la page
    'sub_menu' => 'options-general.php',

    // Droit nécessaire pour pouvoir accéder à cette page
    'capability' => 'manage_options',

    // Slug de la page
    'menu_slug' => 'docalist-options',
    
    // ID de l'icone à utiliser à droite du titre de la page 
    // (cf. doc dans docalist-core/parts/admin-pages/tools.php)
    'icon' => 'options-general',

    // Nom de l'option qui sera enregistrée dans la table wp_options
    // C'est également le nom que les meta boxes (/parts/settings) doivent 
    // indiquer dans l'entête de fichier (clé Setting). 
    'setting' => 'docalist-options',

    // Nom de l'onglet par défaut
    // 'default_tab' => 'General'
);
