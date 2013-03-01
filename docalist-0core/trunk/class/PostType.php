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
use Docalist\Forms\Assets, Docalist\Forms\Themes;

/**
 * Représente un Custom Post Type WordPress.
 *
 * C'est un Container qui gère les metaboxes associées.
 */
abstract class PostType extends Registrable implements Container {
    // TraitContainer : remplacer implements par use

    /*
     * Notes
     *
     * Inclure les meta dans les révisions Wordpress :
     * http://lud.icro.us/post-meta-revisions-wordpress
     */

    /**
     * Le nom du nonce WordPress qui sera généré dans l'écran d'édition.
     */
    const NONCE = 'dcl_nonce';

    /**
     * Le nom du meta utilisé pour stocker les données de l'enregistrement.
     */
    const META = 'dcl_data';

    /**
     * @var array Interface Container, liste des metaboxes de ce post type.
     */
    protected $items = array(); // TraitContainer : à enlever

    /**
     * @var Thème de formulaire à utiliser pour les metaboxes.
     */
    protected $theme = 'wordpress';


    /**
     * Retourne les options à utiliser pour définir ce type de contenu dans
     * wordpress.
     *
     * @return array les arguments à passer à la fonction register_post_type()
     * de WordPress.
     */
    abstract protected function options();

    /**
     * Retourne le thème de formulaire à utiliser pour les metaboxes.
     *
     * @return string
     */
    public function theme() {
        return $this->theme;
    }


    /**
     * @inheritdoc
     */
    public function register() {
        // Récupère les paramètres du custom post type à créer
        $id = $this->id();
        $options = $this->options();

        // Déclare le custom post type dans WordPress
        register_post_type($id, $options);

        // Génère un nonce lorsque l'écran d'édition est affiché
        add_action('edit_form_after_title', function () {
            $this->createNonce();
        });

        // Définit le callback utilisé pour initialiser les metaboxes
        add_action('add_meta_boxes_' . $id, function($post){
            $this->loadMetaboxes($post);
        });

        // Définit le callback utilisé pour enregistrer les données
        add_action('post_updated', function($id, WP_Post $post, WP_Post $previous) {
            if ($this->checkNonce()) {
                $this->registerMetaboxes();
                $this->saveMetaboxes($post, $previous);
            } else die('nonce invalide');
        }, 10, 3);
    }

    /**
     * Enregistre dans WordPress les metabox associées à ce PostType.
     *
     * Cette méthode est destinée à être surchargée par les classes
     * descendantes.
     *
     * Elle est appellée par WordPress lorsque l'écran de saisie est
     * affiché (c'est le callback passé en paramètre à register_post_type).
     *
     * Vous pouvez créer de nouvelles métabox ($this->add(new Metabox)) ou
     * supprimer les metabox existantes créées automatiquement par WordPress.
     */
    protected function registerMetaboxes() {
    }

    /**
     * Génère un nonce WordPress lorsque l'écran d'édition du post type
     * est affiché.
     */
    protected function createNonce() {
        if (get_post_type() === $this->id()) {
            wp_nonce_field('edit-post', self::NONCE);
        }
    }

    /**
     * Vérifie que $_POST contient le nonce créé par createNonce() et que
     * celui-ci est valide.
     *
     * @return bool
     */
    protected function checkNonce() {
        return isset($_POST[self::NONCE]) && wp_verify_nonce($_POST[self::NONCE], 'edit-post');
    }

    /**
     * Charge les metaboxes et initialise leur contenu lorsque l'écran
     * d'édition est affiché.
     *
     * @param WP_Post $post Le post WordPress en cours.
     */
    private function loadMetaboxes(WP_Post $post) {
        // Charge les données de l'enregistrement
        $data = get_post_meta($post->ID, self::META, true);
        empty($data) && $data = array();

        // Enregistre les metaboxes dans WordPress
        $this->registerMetaboxes();

        // Initialise les metaboxes avec le contenu de l'enregistrement
        $assets = Themes::assets($this->theme());
        foreach ($this->items as $metabox) {
            $metabox->bind($data);
            $assets->add($metabox->getAssets());
        }
        // Insère tous les assets dans la page
        foreach ($assets as $asset) {
            if (isset($asset['src']) && false === strpos($asset['src'], '//')) {
                $asset['src'] = plugins_url('docalist-0core/lib/docalist-forms/'.$asset['src']);
            }

            // Fichiers JS
            if ($asset['type'] === Assets::JS) {
                wp_enqueue_script(
                    $asset['name'],
                    $asset['src'],
                    array(),
                    $asset['version'],
                    $asset['position'] === Assets::BOTTOM
                );
            }

            // Fichiers CSS
            else {
                wp_enqueue_style(
                    $asset['name'],
                    $asset['src'],
                    array(),
                    $asset['version'],
                    $asset['media']
                );
            }
        }
    }

    /**
     * Enregistre les données de l'enregistrement lorsque le post est
     * sauvegardé.
     *
     * @param WP_Post $post Le post WordPress en cours.
     * @param WP_Post $previous La version précédente du post.
     */
    private function saveMetaboxes(WP_Post $post, WP_Post $previous) {
        $data = array();
        foreach ($this->items as $metabox) {
            $metabox->bind($_POST);
            $data += $metabox->data();
        }

        $this->filterData($data);
        update_post_meta($post->ID, self::META, $data);
    }

    /**
     * Filtre les données à enregistrer, supprime les valeurs vides.
     *
     * @param array $data Les données à enregistrer.
     */
    private function filterData(array & $data) {
        foreach ($data as $key => & $value) {
            if (is_array($value)) {
                $this->filterData($value);
            }
            if (empty($value)) {
                unset($data[$key]);
            }
        }
        if (empty($data)) $data = null;
    }


    /**
     * Interface Container. A remplacer par un Trait.
     *
     * @inheritdoc
     */
    public function get($name) {
        // TraitContainer : supprimer cette méthode
        return Utils::containerGet($this, $this->items, $name);
    }

    /**
     * Interface Container. A remplacer par un Trait.
     *
     * @inheritdoc
     */
    public function add(Registrable $object) {
        // TraitContainer : supprimer cette méthode
        return Utils::containerAdd($this, $this->items, $object);
    }


/*
        // Crée les custom post statuses
        //todo

        // Définit le hook wordpress pour créer les metaboxes de ce post type
        add_action('add_meta_boxes_' . $this->id(), function() {
            global $post;

            // Crée toutes les metaboxes définies pour ce post type
            $this->createMetaboxes(true);

            // Nouveau post, charge les valeurs par défaut du post type
            if ($post->post_status === 'auto-draft') {
                $defaults = $this->defaults();
                foreach ($this->items as $metabox) {
                    $metabox->bind($defaults);
                }
            }

            // Edition pour modification d'un enregistrement existant
            else {
                foreach ($this->items as $metabox) {
                    $metabox->bind($post);
                }
            }
        });

        // Sauvegarde les données quand l'enreg ets mis à jour
        add_action('post_updated', function($id, WP_Post $post, WP_Post $previous) {
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }

            // Crée toutes les metaboxes sans les référencer
            $this->createMetaboxes(false);

            foreach ($this->items as $metabox) {
                $metabox->bind($_POST);
                var_export($metabox->data());

                foreach ($metabox->data() as $key => $value) {
                    \update_post_meta($post->ID, $key, $value);
                }
            }
        }, 10, 3);
 */
}
