<?php
/**
 * This file is part of a "Docalist Core" plugin.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package Docalist
 * @subpackage Core
 * @author Daniel Ménard <daniel.menard@laposte.net>
 * @version SVN: $Id$
 */
namespace Docalist\Data\Repository;

use Docalist\Data\Entity\EntityInterface;
use Docalist\Utils;
use WP_Post;
use Exception, StdClass;

/**
 * Un dépôt dans lequel les entités sont stockées sous forme de Custom Post
 * Types WorPress.
 */
class PostTypeRepository extends AbstractRepository {
    /**
     * Le nom du meta (custom field) utilisé pour stocker les données
     * sérialisées de l'entité au sein de la table wp_post_meta.
     *
     * @var string
     */
    const META_KEY = 'dcl_data';

    /**
     * Le nom du custom post type, c'est-à-dire la valeur qui sera stockée dans
     * le champ post_type de la table wp_posts pour chacun des documents créés.
     *
     * @var string
     */
    protected $postType;

    /**
     * Crée un nouveau dépôt.
     *
     * @param string $type le nom complet de la classe Entité utilisée pour
     * représenter les enregistrements de ce dépôt.
     *
     * @param string $postType Le nom du custom post type.
     *
     * @throws Exception Si $type ne désigne pas une classe d'entité.
     */
    public function __construct($type, $postType) {
        parent::__construct($type);
        $this->postType = $postType;
    }

    /**
     * Retourne le nom du custom post type, c'est-à-dire la valeur qui sera
     * stockée dans le champ post_type de la table wp_posts pour chacun des
     * documents créés.
     *
     * @return string
     */
    public function postType() {
        return $this->postType;
    }

    public function load($entity, $type = null) {
        // Vérifie qu'on a une clé
        $primaryKey = $this->checkPrimaryKey($entity, true);

        // Vérifie le type d'entité
        $this->checkType($type);

        // WP_Post::get_instance($primaryKey); erreur si existe pas ?

        $data = get_post_meta($primaryKey, static::META_KEY, true);
        $data = json_decode($data, true);
        $entity = new $type($data);
        $entity->primaryKey($primaryKey);

        return $entity;
    }

    /**
     * Synchronise le post WordPress à partir des données de l'entité.
     *
     * @param WP_Post $post
     * @param EntityInterface $entity
     */
    protected function synchronizePost(WP_Post & $post, EntityInterface $entity) {
        global $user_ID;

        $post->post_type = $this->postType();
        $post->post_status = 'publish'; // TODO: config
        $post->post_author = $user_ID; // TODO: config

        $post->post_date = $post->post_modified = current_time('mysql');
        $post->post_date_gmt = $post->post_modified_gmt = current_time('mysql', true);

        $post->comment_status = 'closed'; // TODO: config ?
        $post->ping_status = 'closed'; // TODO: config ?
        $post->guid = 'http://' . Utils::uuid();
        // le guid wp doit obligatoirement commencer par http://
        // cf. http://alexking.org/blog/2011/08/13/wordpress-guid-format
    }

    public function store(EntityInterface $entity) {
        global $wpdb;

        // Vérifie que l'entité est du bon type
        $this->checkType($entity);

        // Récupère la clé de l'entité
        $primaryKey = $entity->primaryKey();

        // Charge le post existant si on a une clé, créée un nouveau post sinon
        if ($primaryKey) {
            if (false === $post = WP_Post::get_instance($primaryKey)) {
                throw new Exception("Post $primaryKey not found");
            }
        } else {
            $post = new WP_Post(new StdClass()); // wp oblig à passer un objet vide...
            echo 'nouveau post = <pre>', var_export($post, true), '</pre>';
        }

        // Synchronise le post wp à partir des données de l'entité
        $this->synchronizePost($post, $entity);
        echo 'après synchronisePost = <pre>', var_export($post, true), '</pre>';
        // $post = $post->to_array();

        // Pour wpdb, il faut maintenant un tableau et non plus un WP_Post
        $post = (array) $post;
        unset($post['filter']);
        unset($post['format_content']);

        // Met à jour le post si on a une clé
        if ($primaryKey) {
            if (false === $wpdb->update($wpdb->posts, $post, array('ID' => $primaryKey))) {
                throw new Exception($wpdb->last_error);
            }
            echo "L'enreg $primaryKey a été mis à jour<br />";
        }

        // Crée un nouveau post sinon
        else {
            if (false === $wpdb->insert($wpdb->posts, $post)) {
                throw new Exception($wpdb->last_error);
            }
            $primaryKey = (int) $wpdb->insert_id;
            $entity->primaryKey($primaryKey);
            echo "L'enreg $primaryKey a été créé<br />";
        }

        // Enregistre les données de l'entité sous forme de meta json
        $data = json_encode($entity, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        update_post_meta($primaryKey, static::META_KEY, $data);
    }

    public function delete($entity) {
        global $wpdb;

        $primaryKey = $this->checkPrimaryKey($entity, true);

        $result = $wpdb->delete($wpdb->posts, array('ID' => $primaryKey));
        if ($result === false) {
            $msg = 'Unable to delete entity %s : %s';
            throw new Exception($msg, $primaryKey, $wpdb->last_error);
        } elseif ($result === 0) {
            $msg = 'Entity %s not found';
            throw new Exception($msg, $primaryKey);
        }
    }
}