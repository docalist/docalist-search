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
 * Des actions pour un plugin qui font l'objet d'une page dans le menu
 * de WordPress.
 */
abstract class AbstractAdminPage extends AbstractActions {
    /**
     * @inheritdoc
     */
    static protected $parameterName = 'page';

    /**
     * @inheritdoc
     */
    protected $parentPage = '';

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
        $capability = $this->capability();
        if (current_user_can($capability)) {
            add_action('admin_menu', function() use ($capability){
                //@formatter:off
                if (empty($this->parentPage)) {
                    \add_menu_page(
                        $this->pageTitle(),
                        $this->menuTitle(),
                        $capability,
                        $this->id(),
                        function(){
                            $this->run();
                        }
                    );
                } else {
                    \add_submenu_page(
                        $this->parentPage,
                        $this->pageTitle(),
                        $this->menuTitle(),
                        $capability,
                        $this->id(),
                        function(){
                            $this->run();
                        }
                    );
                }
                //@formatter:on
            });
        }
    }

    /**
     * @inheritdoc
     */
    protected function run() {
        echo '<div class="wrap">';
        screen_icon($this->parentPage ? '' : 'generic');
        printf('<h2>%s</h2><p class="description">%s</p>', $this->title(), $this->description());

        parent::run();

        echo '</div>';
    }

}
