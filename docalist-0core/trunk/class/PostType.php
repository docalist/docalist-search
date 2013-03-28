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
 * @version     SVN: $Id: PostType.php 460 2013-03-01 17:40:28Z
 * daniel.menard.35@gmail.com $
 */

namespace Docalist;
use Exception, WP_Post;
use Docalist\Forms\Assets, Docalist\Forms\Themes;

/**
 * Représente un Custom Post Type WordPress.
 *
 * C'est un Container qui gère les metaboxes associées.
 */
abstract class PostType implements ContainerInterface {
    use ContainerTrait;

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
     * @var Thème de formulaire à utiliser pour les metaboxes.
     */
    protected $theme = 'wordpress';

    /**
     * @var array Permet de recopier dans l'enregistremt Post géré par
     * WordPress un ou plusieurs des champs du type de contenu personnalisé.
     *
     * Doit être surchargé par les classes descendantes.
     *
     * Format du tableau :
     * champ Wordpress => champ du custom post type
     */
    protected $copyFields = array();

    /**
     * Retourne les options à utiliser pour définir ce type de contenu dans
     * wordpress.
     *
     * @return array les arguments à passer à la fonction register_post_type()
     * de WordPress.
     */
    abstract protected function registerOptions();

    /**
     * Retourne le thème de formulaire à utiliser pour les metaboxes.
     *
     * @return string
     */
    public function theme() {
        return $this->theme;
    }

    private function checkCopyFields() {
        if (is_null($this->copyFields)) {
            throw new Exception('Vous devez initialiser $copyFields dans la classe ' . get_class($this));
        }
/*
        $defaults = array(
            // Nom du champ utilisé comme post_title
            'post_title' => 'title',

            // Nom du champ utilisé comme slug
            'post_name' => 'ref',

            // Statut par défaut des enregistrements
            'post_status' => 'publish',
        );

        $copyFields = (object)(static::$copyFields + $defaults);

        if (!$copyFields->post_title) {
            throw new Exception('post_title requis dans copyFields');
        }

        if (!$copyFields->post_name) {
            throw new Exception('post_name requis dans copyFields');
        }

        if (!$copyFields->post_status) {
            throw new Exception('post_status requis dans copyFields');
        }
        static::$copyFields = $copyFields;
*/
    }

    /**
     * @inheritdoc
     */
    public function register() {
        //
        $this->checkcopyFields();

        // L'id d'un CPT doit être définit une fois pour toute
        if (!isset($this->id)) {
            throw new Exception('Vous devez initialiser $id dans la classe ' . get_class($this));
        }
        $id = $this->id;

        // Récupère les paramètres du custom post type à créer
        $options = $this->registerOptions();

        // Déclare le custom post type dans WordPress
        register_post_type($this->id, $options);

        // Génère un nonce lorsque l'écran d'édition est affiché
        add_action('edit_form_after_title', function() {
            $this->createNonce();
        });

        // Définit le callback utilisé pour initialiser les metaboxes
        add_action('add_meta_boxes_' . $this->id, function($post) {
            $this->loadMetaboxes($post);
        });

        // Définit le callback utilisé pour enregistrer les données
        add_action('post_updated', function($id, WP_Post $post, WP_Post $previous) {
            if ($this->checkNonce()) {
                $this->registerMetaboxes();
                $this->saveMetaboxes($post, $previous);
            }
        }, 10, 3);
    }

    /**
     * Formatte le contenu de l'enregistrement pour l'afficher sous forme
     * de chaine lorsque the_content() est appellée.
     * = représentation par défaut d'un enreg quel que soit le thème
     * Peut être surchargée par les classes descendantes.
     */
    protected function asContent(array $data) {
        $ol = count(array_filter(array_keys($data), 'is_string')) === 0;

        $content = $ol ? '<ol>' : '<ul style="list-style-type: none; padding: 0; margin: 0;">';
        foreach ($data as $key => $value) {
            //
            $content .= '<li>';

            // Nom du champ
            if (is_string($key))
                $content .= "<b>$key</b> : ";

            // Valeur simple
            if (is_scalar($value)) {
                $content .= $value;
            }

            // Tableaux
            else {
                $allScalar = count(array_filter($value, 'is_array')) === 0;
                $hasKeys = count(array_filter(array_keys($value), 'is_string')) !== 0;

                $canImplode = $allScalar && !$hasKeys;
                $canInline = $allScalar && $hasKeys && (count($value) < 5);

                if ($canImplode) {
                    $content .= implode(', ', $value);
                } elseif ($canInline) {
                    foreach ($value as $key => $value) {
                        $content .= "<i>$key</i>=$value, ";
                    }
                    $content = rtrim($content, ', ');
                } else {
                    $content .= $this->asContent($value);
                }
            }
            $content .= '</li>';
        }
        $content .= $ol ? '</ol>' : '</ul>';
        return $content;
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
        if (get_post_type() === $this->id) {
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
        Utils::enqueueAssets($assets);
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
        // Détermine s'il s'agit d'une liste de valeurs (i.e les différentes
        // occurences d'un champ répétable) ou bien d'un groupe de champs.
        // Les clés sont des entiers dans le 1er cas, des chaines dans le 2nd.
        $isValues = is_int(key($data));

        // Si c'est une liste de valeurs, on a besoin de connaître la taille
        // initiale du tableau pour le renuméroter si jamais des éléments sont
        // supprimés (cf plus bas).
        $isValues && $count = count($data);

        // Filtre les données en supprimant tous les éléments vides du tableau
        foreach($data as $key => &$value) {
            is_array($value) && $this->filterData($value);
            if (empty($value)) {
                unset($data[$key]);
            }
        }

        // On ne stocke pas les champs vides
        if (empty($data)) {
            $data = null;
        }

        // Si le tableau contient une liste d'occurences et qu'on a supprimé
        // des éléments, il faut renuméroter le tableau pour garantir que les
        // clés sont croissantes et sans trous, sinon, json_encode() va
        // considérer que c'est un objet et non pas un tableau, ce qui génère
        // alors une exception dans ElasticSearch.
        elseif ($isValues && $count != count($data)) {
            $data = array_values($data);
        }
    }

    /**
     * Ajoute ou met à jour un enregistrement.
     *
     * Si le record a déjà un ID, l'enregistrement existant est mis à jour,
     * sinon un nouvel enregistrement est créé.
     */
    public function store($record) {
        /**
         * @var \wpdb
         */
        global $wpdb;
        global $user_ID;

        if (is_object($record)) {
            $record = (array)$record;
        }
        $this->filterData($record);

        $id = isset($record['id']) ? $record['id'] : null;

        if ($id) {
            $post = \WP_Post::get_instance($id);
            if ($post === false) {
                throw new Exception("Post id $id not found");
            }
        } else {
            $post = get_class_vars('WP_POST');
            unset($post['filter']);
            foreach($this->copyFields as $wp => $field) {
                if (isset($record[$field])) {
                    $post[$wp] = $record[$field];
                } else {
                    // donner une valeur par défaut
                }
            }
            $post['post_type'] = $this->id;
            $post['post_status'] = 'publish'; // TODO: config
            $post['post_author'] = $user_ID; // TODO: config
            $post['post_date'] = $post['post_modified'] = \current_time('mysql');
            $post['post_date_gmt'] = $post['post_modified_gmt'] = \current_time('mysql', true);
            $post['comment_status'] = 'closed'; // TODO: config ?
            $post['ping_status'] = 'closed'; // TODO: config ?
            $post['guid'] = 'http://' . Utils::uuid();
            // le guid wp doit obligatoirement commencer par http://
            // cf. http://alexking.org/blog/2011/08/13/wordpress-guid-format

            // Insertion normale via wordpress
            $useWordpress = false;
            if ($useWordpress) {
                $id = \wp_insert_post($post, true);
                if (is_wp_error($id)) {
                    throw new Exception($id->get_error_message());
                }
            }

            // Insertion directe dans la base
            else {
                if ( false === $wpdb->insert($wpdb->posts, $post) ) {
                    throw new Exception($wpdb->last_error);
                }
                $id = (int) $wpdb->insert_id;

//                echo "ID=$id<br />Post=<pre>", print_r($post,true), '</pre>';
//die();
            }
        }

        update_post_meta($id, self::META, $record);
    }

    private function createPost($record) {

    }

    /**
     * Retourne l'enreg dont l'ID est passé en paramètre
     */
    public function retrieve($id) {// fetch
        return \WP_Post::get_instance($id);
    }

    /**
     * Supprime l'enregistrement dont l'ID est passé en paramètre
     */
    public function delete($id) {
        \wp_delete_post($id, true);
    }
}
