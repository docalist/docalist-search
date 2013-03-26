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
use Docalist\Forms\Assets;

/**
 * Des actions pour un plugin qui font l'objet d'une page dans le menu
 * de WordPress.
 */
abstract class AbstractAdminPage extends AbstractActions {
    /**
     * @inheritdoc
     */
    static protected $hookName = 'admin_menu';

    /**
     * @inheritdoc
     */
    static protected $parameterName = 'page';

    /**
     * @inheritdoc
     */
    protected $parentPage = '';

    /**
     * Le constructeur est final : il ne peut pas être surchargé dans les
     * classes descendantes.
     *
     * Surchargez la méthode load() pour faire vos initialisations.
     */
    public final function __construct() {

    }

    /**
     * Retourne le titre à utiliser pour afficher la page dans le menu
     * WordPress.
     *
     * Par défaut, la méthode retourne pageTitle() : le titre affiché
     * dans le menu est le même que le titre affiché dans la page.
     *
     * Les classes descendantes doivent surcharger cette méthode si elles
     * veulent un libellé différent dans le menu.
     *
     * @return string
     */
    protected function menuTitle() {
        return $this->pageTitle();
    }

    /**
     * @inheritdoc
     */
    public function register() {
        // One fait quelque chose que si l'utilisateur a les droits requis
        $capability = $this->defaultCapability();
        if (! current_user_can($capability)) {
            return;
        }

        // Paramètres dont on a besoin
        $id = $this->id();
        $title = $this->pageTitle();
        $menu = $this->menuTitle();
        $parent = $this->parentPage;

        // Crée la page dans le menu WordPress
        if (empty($parent)) {
            $page = add_menu_page($title, $menu, $capability, $id);
        } else {
            $page = add_submenu_page($parent, $title, $menu, $capability, $id);
        }

        // Initialisation de la page (création du formulaire par exemple)
        add_action("load-$page", function() {
            $this->load();
        });

        // Gestion des assets. cf http://wordpress.stackexchange.com/a/21579
        add_action('admin_enqueue_scripts', function($hook) use($page) {
            if ($hook === $page) {
                $this->enqueueAssets();
            }
            // TODO: ce serait mieux si WordPress avait un hook de la forme
            // admin_enqueue_scripts-$PAGE car cela éviterait que toutes les
            // pages soient appellées et aient à tester si l'appel les concerne
            // ou pas.
            // On pourrait facilement émuler ça en créant, lors du premier
            // appel, un hook unique qui se chargerait de générer un hook
            // spécifique à chaque page :
            // do_action("admin_enqueue_scripts-$hook")
            // Probablement en namespaçant pour éviter tout conflit :
            // do_action("docalist-core-admin_enqueue_scripts-$hook")
            // L'iéde pourrait être étendue s'il y a d'autres hooks qui nous
            // manquent : le plugin Core se chargerait de tous les créer.
            // A creuser.
        });

        // Fin de la section head de la page : gestion des assets
        add_action("admin_head-$page", function() {
            $this->head();
        });

        // Exécution de la page : affichage
        add_action($page, function() {
            $this->run();
            // Ce hook est équivalent au callback qu'on peut passer en
            // paramètre à add_menu_page() ou add_sub_menu_page(). On
            // ajoute le hook nous-même pour éviter de répéter la closure
            // deux fois lors de la création de la page.
        });

        // Exécution de la page : affichage
        add_action("admin_footer-$page", function() {
            $this->footer();
        });
    }

    /**
     * Initialise la page.
     *
     * Cette méthode est appellée quand WordPress s'apprête à charger votre
     * page. Surchargez cette méthode pour faire les initialisations dont vous
     * avez besoin (créer un formulaire, etc.)
     *
     * @see http://codex.wordpress.org/Plugin_API/Action_Reference/load-(page)
     */
    protected function load() {
    }

    /**
     * Enregistre dans WordPress les assets dont a besoin la page.
     *
     * La méthode appelle {@link getAssets()} et ajoute les CSS et JS
     * obtenus dans la liste de WordPress.
     *
     * Normallement, vous n'avez pas besoin de surcharger cette méthode
     * (surchargez plutôt {@link getAssets()}).
     */
    protected function enqueueAssets() {
        $assets = $this->getAssets();
        $assets && Utils::enqueueAssets($assets);
    }

    /**
     * Génère la section head de la page.
     *
     * Cette méthode est appellée juste avant que WordPress ne termine la
     * génération de la section <head></head> de votre page.
     *
     * Par défaut, la méthode ne fait rien.
     *
     * Vous pouvez la surchargez si vous avez des choses supplémentaires à
     * générer dans la section head de la page (metas, scripts ou styles
     * inline, etc.)
     */
    protected function head() {
    }

    /**
     * @inheritdoc
     */
    protected function run() {
        echo '<div class="wrap">';
        screen_icon($this->parentPage ? '' : 'generic');
        printf('<h2>%s</h2>', $this->pageTitle());
        printf('<p class="description">%s</p>', $this->description());

        parent::run();

        echo '</div>';
    }

    /**
     * Génère le pied de page de la page.
     *
     * Cette méthode est appellée quand WordPress est en train de générer le
     * pied de page de votre  page.
     *
     * La méthode par défaut ne fait rien.
     *
     * Vous pouvez surchargez cette méthode si vous avez des choses à générer
     * dans votre pied de page.
     */
    protected function footer() {
    }

    /**
     * Retourne les assets (fichiers JS et CSS) dont a besoin la page.
     *
     * @return Assets
     */
    public function getAssets() {
        return new Assets();
    }

    /**
     * Ajoute une ou plusieurs actions de ce module comme options dans
     * le menu dont le slug est passé en paramètre.
     *
     * @param string|string[] L'action ou les actions à ajouter au menu.
     * @param string Le slug du menu dans lequel il faut ajouter les actions.
     */
    public function addToMenu($action, $menuSlug) {
        foreach((array) $action as $action) {
            add_submenu_page(
                $menuSlug,
                '',
                $this->title($action),
                $this->capability($action),
                $this->url($action, true)
            );
        }
    }
}
