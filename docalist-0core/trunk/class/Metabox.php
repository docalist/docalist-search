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
 * @version     SVN: $Id: Metabox.php 447 2013-02-27 15:00:13Z
 * daniel.menard.35@gmail.com $
 */

namespace Docalist;

use Docalist\Forms\Themes, Docalist\Forms\Fields;
use Exception, WP_Post;

/**
 * Représente une metabox
 */
abstract class Metabox extends Registrable {
    /**
     * @var Fields Le formulaire à afficher pour cette metabox.
     */
    protected $form;

    /**
     * @var string Position de la metabox dans l'écran d'édition. Cette propiété
     * combine les arguments 'context' et 'priority' de la fonction add_meta_box
     * de WordPress, séparés par un tiret. Exemple : 'side-high'.
     * Valeurs possibles :
     * Context  : 'normal', 'advanced', or 'side'
     * Priority : 'high', 'core', 'default' or 'low'
     */
    protected $position = 'normal-default';

    public function taxonomy($name) {
        $terms = get_terms($name, array(
            'hide_empty' => false,
        ));

        $result = array();
        foreach ($terms as $term) {
            $result[$term->slug] = $term->name;
        }

        return $result;
    }

    /**
     * Crée le formulaire à afficher pour cette metabox et initialise
     * {@link $forms}.
     */
    public function __construct() {

    }

    protected function hookName() {
        return 'add_meta_boxes_' . $this->parent->id();
    }

    /**
     * @inheritdoc
     */
    public function register() {
        // Détermine le titre de la metabox
        $title = $this->form ? $this->form->label() : $this->id();

        // Détermine la position de la metabox
        $context = strtok($this->position(), '-') ? : 'advanced';
        $priority = strtok('¤') ? : 'default';

        // @formatter:off
        add_meta_box(
            $this->id(),            // id metabox
            $title,                 // titre
            array($this, 'render'), // callback
            $this->parent->id(),    // postype
            $context,               // contexte
            $priority               // priorité
        );
        // @formatter:on
    }

    public function bind($data) {
        $this->form && $this->form->bind($data);
    }
    public function data() {
        return $this->form ? $this->form->data() : array();
    }

    public function render() {
        // Pas de formulaire, render() non surchargée : affiche un message
        if (!$this->form) {
            $msg = __('Surchargez __construct() ou render() dans la classe %s pour ajouter un contenu à cette metabox.', 'docalist-core');
            printf($msg, Utils::classname($this));

            return;
        }

        // Le titre du formulaire a déjà été affiché par add_meta_box
        $this->form->label(false);

        // Affiche le formulaire
        $this->form->render($this->parent->theme());
    }

    /**
     * Retourne la position à laquelle cette metabox doit être affichée dans
     * l'écran d'édition. Cette propiété combine les arguments 'context' et
     * 'priority' de la fonction add_meta_box de WordPress, séparés par un
     * tiret. Exemple : 'side-high'.
     *
     * @return string
     */
    public function position() {
        return $this->position;
    }

    /**
     * Retourne les fichiers javascript et css qui sont nécessaires pour
     * cette metabox.
     *
     * @return Assets
     */
    public function getAssets() {
        return $this->form ? $this->form->assets() : null;
    }

}
