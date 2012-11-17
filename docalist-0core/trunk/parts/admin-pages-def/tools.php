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
 * Crée la page "outils" de Docalist.
 *
 * Références :
 * - @link http://codex.wordpress.org/Function_Reference/add_menu_page
 * - @link http://codex.wordpress.org/Function_Reference/add_submenu_page
 * - @link http://codex.wordpress.org/Function_Reference/screen_icon
 */
return array(
    // Titre de la page
    'page_title' => _x('Docalist tools', 'Tools page title', 'docalist-core'),

    // Libellé du menu
    'menu_title' => _x('Docalist', 'Tools page menu label', 'docalist-core'),

    // Nom du menu auquel va être rattachée la page
    'sub_menu' => 'tools.php',

    // Droit nécessaire pour pouvoir accéder à cette page
    'capability' => 'manage_options',

    // Slug de la page
    'menu_slug' => 'docalist-tools',
    
    // ID de l'icone à utiliser à droite du titre de la page 
    'icon' => 'tools',
    
    // Remarques sur la clé "icon".
    //  
    // Les icones dispos dans wp sont définies dans le fichier de sprite 
    // wp-admin/images/icons32.png et consoeurs.
    // 
    // Elles sont utilisées dans la css de wp (cf par exemple le fichier
    // wp-admin/css/wordpcolors-fresh.dev.css) sous la forme d'ID
    // (rechercher le commentaire /* Screen Icons */ et les #icon-* qui suivent)
    // 
    // Piklist utilise la fonction screen_icon() de wp pour générer l'icone.
    // 
    // Il faut indiquer ici la partie de l'ID utilisée pour générer l'icone.
    // par exemple "TOTO" générera <div id="icon-ABCD" class="icon32" />
    // 
    // A ce jour, les ID d'icones standard de wordpress sont (dans l'ordre
    // où elles apparaissent dans le sprite) :
    // themes, edit-comments, index, link, upload, page, plugins, tools, 
    // options-general, post, users et ms-admin.
);
