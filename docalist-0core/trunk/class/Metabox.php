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

use Docalist\Forms\Fields;
use Exception, WP_Post;

/**
 * Représente une metabox
 */
class Metabox extends Fields {
    /**
     * @var PostType Le PostType auquel est rattaché cette metabox.
     * Doit être surchargé par les classes descendantes.
     */
    protected $postType;

    /**
     * @var string Position de la metabox dans l'écran d'édition. Cette propiété
     * combine les arguments 'context' et 'priority' de la fonction add_meta_box
     * de WordPress, séparés par un tiret. Exemple : 'side-high'.
     */
    protected $position = 'advanced-default';

    /**
     * @var Thème à utiliser pour afficher le formulaire de la metabox.
     */
    static protected $theme = 'default';

    /**
     * Crée une nouvelle metabox pour le post type passé en paramètre.
     *
     * @var PostType $postType
     */
    public function __construct(PostType $postType) {
        // parent::__construct();

        $this->postType = $postType;
        $this->setup();
    }

    /**
     * Crée les éléments de formulaire que contient cette metabox.
     *
     * Cette méthode est appellée par le constructeur dès que la metabox est
     * créée (pour les classes descendantes, c'est plus simple de surcharger
     * cette méthode plutôt que le constructeur).
     *
     * Exemple :
     * (todo)
     */
    public function setup() {

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
     * Retourne le thème à utiliser pour afficher le formulaire de la metabox.
     *
     * @return string
     */
    public function theme() {
        return $this->theme;
    }

    /**
     * Retourne l'identifiant unique de cette metabox.
     *
     * Par défaut, il s'agit d'une chaine qui combine le nom de base de la
     * classe du post type et le nom de base de la classe de la metabox, le
     * tout en minuscules (par exemple : reference-type).
     *
     * @return string
     */
    public function id() {
        return $this->postType->name() . '-' . $this->type();
    }

    /**
     * Enregistre cette metabox dans WordPress.
     */
/*
    public function register() {
        $context = strtok($this->position, '-') ?: 'advanced';
        $priority = strtok('¤') ?: 'default';

        $id = $this->id();
        $title = $this->title();

        if (WP_DEBUG && empty($title)) {
            $msg = __('La metabox %s doit surcharger la méthode title()', 'docalist-biblio');
            throw new Exception(sprintf($msg, get_class($this)));
        }
        //echo "appel de add_metabox pour ", $this->id(), "<br />";
        add_meta_box($this->id(), $this->title(), array($this, 'render'), $this->postType->id(), $context, $priority);
    }
*/

}
