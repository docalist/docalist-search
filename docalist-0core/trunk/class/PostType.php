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
use Exception, WP_Post;

/**
 * Représente un nouveau Custom Post Type WordPress
 */
abstract class PostType extends Registrable {
    /**
     * @var array(string) Liste des metaboxes définies pour ce post type :
     * tableau contenant les noms des classes de chacune des metabox.
     * Les classes doivent désigner des objets de type {@link Metabox}.
     */
    protected $metaboxes = array();

    /**
     * @inheritdoc
     */
    public function register() {
        // Crée le custom post type dans WordPress
        register_post_type($this->id(), $this->options());

        // Crée les custom post statuses
        //todo

        // Définit le hook wordpress pour créer les metaboxes de ce post type
        add_action('add_meta_boxes_' . $this->id(), function() {
            /**
             * @var WP_Post Le post en cours de création ou d'édition.
             */
            global $post;

            // Crée toutes les metaboxes définies pour ce post type
            $this->createMetaboxes(true);

            // Nouveau post, charge les valeurs par défaut du post type
            if ($post->post_status === 'auto-draft') {
                $defaults = $this->defaults();
                foreach ($this->metaboxes as $metabox) {
                    $metabox->bind($defaults);
                }
            }

            // Edition pour modification d'un enregistrement existant
            else {
                foreach ($this->metaboxes as $metabox) {
                    $metabox->bind($post);
                }
            }
        });

        // Saivegarde les données quand l'enreg ets mis à jour
        add_action('post_updated', function($id, WP_Post $post, WP_Post $previous) {
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }

            // Crée toutes les metaboxes sans les référencer
            $this->createMetaboxes(false);

            foreach ($this->metaboxes as $metabox) {
                $metabox->bind($_POST);
                var_export($metabox->data());

                foreach ($metabox->data() as $key => $value) {
                    \update_post_meta($post->ID, $key, $value);
                }
            }
        }, 10, 3);
    }

    /**
     * Crée les metaboxes.
     */
    protected function createMetaboxes($register = false) {
        if (WP_DEBUG && is_object(reset($this->metaboxes))) {
            throw new Exception('Metaboxes already created');
        }

        $this->metaboxes = array_flip($this->metaboxes);
        foreach ($this->metaboxes as $name => &$metabox) {
            // Crée et initialise la metabox
            $metabox = new $name($this);

            // Référence la metabox dans wordpress si on nous a dit de le faire
            if ($register) {
                // Détermine la position de la metabox
                $context = strtok($metabox->position(), '-') ? : 'advanced';
                $priority = strtok('¤') ? : 'default';

                // @formatter:off
                add_meta_box(
                    $metabox->id(),                         // id metabox
                    $metabox->label() ?: $metabox->id(),    // titre
                    function() use($metabox) {              // callback
                        $metabox->render();
                    },
                    $this->id(),                            // postype
                    $context,                               // contexte
                    $priority                               // priorité
                );
                // @formatter:on
            }
        }
    }

    protected function saveMetaboxes() {
        global $post;

        die("save");
    }

    /**
     * Retourne les options à utiliser pour définir ce type de contenu dans
     * wordpress.
     *
     * @return array les arguments à passer à la fonction register_post_type()
     * de WordPress.
     */
    abstract protected function options();

    /**
     * retourne les valeurs par défaut pour ce type de contenu.
     *
     * @return array
     */
    public function defaults() {
        return array('journal' => "Pif Gadget");
    }

}
