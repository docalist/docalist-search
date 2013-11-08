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
use InvalidArgumentException, RuntimeException;
use StdClass;

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
     * @throws InvalidArgumentException Si $type ne désigne pas une classe d'entité.
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
        // Vérifie qu'on a une clé primaire
        $primaryKey = $this->checkPrimaryKey($entity, true);

        // Charge le post
        $post = WP_Post::get_instance($primaryKey);

        // Récupère les données de l'entité, stockées dans post_excerpt
        $data = $post->post_excerpt;

        // Si c'est un nouveau post, post_excerpt est vide
        if ($data === '') {
            $data = array();
        }

        // Sinon, post_excerpt doit contenir du JSON valide
        else {
            $data = json_decode($post->post_excerpt, true);

            // On doit obtenir un tableau (éventuellement vide), sinon c'est une erreur
            if (! is_array($data)) {
                $msg = 'JSON error %s while decoding field post_excerpt of post %s: %s';
                $msg = sprintf($msg, json_last_error(), $primaryKey, var_export($post->post_excerpt, true));
                throw new RuntimeException($msg);
            }
        }

        // Type = false permet de récupérer les données brutes
        if ($type === false) {
            return $data;
        }

        // Par défaut, on retourne une entité du même type que le dépôt
        if (is_null($type)) {
            $type = $this->type;
        }

        // Sinon le type demandé doit être compatible avec le type du dépôt
        else {
            $this->checkType($type);
        }

        // Crée une entité du type demandé
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
                $msg = 'Post %s not found';
                throw new RuntimeException(sprintf($msg, $primaryKey));
            }
        } else {
            // wp nous oblige à passer un objet vide...
            $post = new WP_Post(new StdClass());
        }

        // Synchronise le post wp à partir des données de l'entité
        // Permet également de modifier l'entité avant enregistrement (par
        // exemple, affecter une valeur au champ Ref).
        $this->synchronizePost($post, $entity);

        // Encode les données de l'entité en JSON
        $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        WP_DEBUG && $options |= JSON_PRETTY_PRINT;
        $data = json_encode($entity, $options);

        // Stocke le JSON dans le champ post_excerpt
        $post->post_excerpt = $data;

        // Pour wpdb, il faut maintenant un tableau et non plus un WP_Post
        $post = (array) $post;
        unset($post['filter']);
        unset($post['format_content']);

        // Met à jour le post si on a une clé
        if ($primaryKey) {
            if (false === $wpdb->update($wpdb->posts, $post, array('ID' => $primaryKey))) {
                throw new RuntimeException($wpdb->last_error);
            }

            // Vide le cache pour ce post (Important, cf WP_Post::get_instance)
            wp_cache_delete($primaryKey, 'posts');
        }

        // Crée un nouveau post sinon
        else {
            if (false === $wpdb->insert($wpdb->posts, $post)) {
                throw new RuntimeException($wpdb->last_error);
            }
            $primaryKey = (int) $wpdb->insert_id;
            $entity->primaryKey($primaryKey);
        }
    }

    public function delete($entity) {
        global $wpdb;

        $primaryKey = $this->checkPrimaryKey($entity, true);

        $result = $wpdb->delete($wpdb->posts, array('ID' => $primaryKey));
        if ($result === false) {
            $msg = 'Unable to delete post %s: %s';
            throw new RuntimeException($msg, $primaryKey, $wpdb->last_error);
        } elseif ($result === 0) {
            $msg = 'Post %s not found';
            throw new RuntimeException(sprintf($msg, $primaryKey));
        }
    }

    public function deleteAll() {
        global $wpdb;

        // Supprime tous les enregs
        $nb = $wpdb->delete($wpdb->posts, array('post_type' => $this->postType));

        // Réinitialise les séquences éventuelles utilisées par cette base
        $this->sequenceClear();

        // Retourne le nombre de notices supprimées
        return $nb;
    }

    /**
     * Méthode utilitaire permettant aux classes descendantes d'implémenter
     * un champ séquence (typiquement : le champ ref des notices).
     *
     * Cette méthode gère une option de la forme "{posttype}_last_{champ}" (par
     * exemple "dclrefbase_last_ref") dans la table wp_options de wordpress.
     *
     * Lors du premier appel, elle crée l'option et retourne la valeur 1. Lors
     * des appels suivants, le compteur est incrémenté et la méthode retourne
     * sa valeur actuelle.
     *
     * @param string $field Nom du champ pour lequel il faut générer une
     * séquence.
     *
     * @return int
     */
    protected function sequenceIncrement($field) {
        global $wpdb;

        // Nom de l'option dans la table wp_options
        $name = $this->sequenceName($field);

        // Requête SQL à exécuter. Adapté de :
        // @see http://answers.oreilly.com/topic/172-how-to-use-sequence-generators-as-counters-in-mysql/
        $sql = "INSERT INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) "
             . "VALUES('$name', 1, 'no') "
             . "ON DUPLICATE KEY UPDATE `option_value` = LAST_INSERT_ID(`option_value` + 1)";

        // Exécute la requête (pas de prepare car on contrôle les paramètres)
        $affectedRows = $wpdb->query($sql);

        return $affectedRows === 1 ? 1 : $wpdb->insert_id;

        // Explications sur la requête SQL :
        // @see http://dev.mysql.com/doc/refman/5.0/en/insert-on-duplicate.html
        //
        // With ON DUPLICATE KEY UPDATE, the affected-rows value per row is
        // - 1 if the row is inserted as a new row
        // - 2 if an existing row is updated
        // - 0 if an existing row is set to its current values (i.e. n'a pas
        //   été modifiée. Attention, dépend du flag CLIENT_FOUND_ROWS)
        //
        // If a table contains an AUTO_INCREMENT column and INSERT ... UPDATE
        // inserts a row, the LAST_INSERT_ID() function returns the
        // AUTO_INCREMENT value (i.e. l'ID de l'enreg créé).
        // If the statement updates a row instead, LAST_INSERT_ID() is not
        // meaningful. However, you can work around this by using
        // LAST_INSERT_ID(expr) : c'est ce qu'on fait sur la dernière ligne,
        // on "initialise" la valeur qui sera retournée par LAST_INSERT_ID().
        //
        // Dans notre cas :
        // - premier appel, la ligne est insérée, insert retourne "affected-rows=1"
        //   donc on sait que le compteur est à 1
        // - appel suivant, la ligne est updatée, insert retourne "affected-rows=2"
        //   il faut appeller LAST_INSERT_ID() pour obtenir la valeur du compteur.
    }

    /**
     * Retourne le nom de la séquence pour le champ indiqué.
     *
     * Le nom de séquence correspond au nom de l'option qui sera créée dans la
     * table wp_options de wordpress si cette séquence est utilisée.
     *
     * @param string $field
     */
    protected function sequenceName($field) {
        return $this->postType . '_last_' . $field;
    }

    /**
     * Réinitialise (supprime) une séquence, ou toutes les séquences d'une base
     * si aucun champ n'est passé en paramètre.
     *
     * @param string $field Nom du champ ou vide pour supprimer toutes les
     * séquences.
     *
     * @return int Le nombre de séquences supprimées.
     */
    protected function sequenceClear($field = null) {
        global $wpdb;

        if ($field) {
            $op = '=';
            $value = $this->sequenceName($field);
        } else {
            $op = ' LIKE ';
            $value = $this->sequenceName('') . '%';
        }

        $sql = "DELETE FROM `$wpdb->options` WHERE `option_name` $op '$value'";

        return $wpdb->query($sql);
    }

    /**
     * Stocke la valeur passée en paramêtre dans la séquence du champ indiqué
     * si celle-ci est supérieure à la valeur actuelle de la séquence.
     *
     * Cette méthode permette de mettre à jour une séquence quand le numéro est
     * fournit par l'extérieur.
     *
     * Exemple d'utilisation :
     *
     * <code>
     *     if (empty($ref))) $ref = sequenceIncrement('ref');
     *     else sequenceSetIfGreater($ref);
     * </code>
     *
     * @param string $field Nom du champ
     * @param int $value Valeur à tester
     *
     * @return int Un code indiquant l'opération réalisée :
     * - 0 : la séquence n'a pas été modifiée (sa valeur actuelle est supérieure
     *   ou égale à $value).
     *
     * - 1 : la ligne a été créée (la séquence n'existait pas encore, elle a
     *   été créée et initialisée avec $value).
     *
     * - 2 : la ligne a été mise à jour (la séquence existait mais sa valeur
     *   était inférieure à $value, elle a été initialisée avec $value).
     */
    protected function sequenceSetIfGreater($field, $value) {
        global $wpdb;

        // Nom de l'option dans la table wp_options
        $name = $this->sequenceName($field);

        // Value doit être un entier
        $value = (int) $value;

        // Requête SQL à exécuter. Adapté de :
        // @see http://stackoverflow.com/a/10081527
        $sql = "INSERT INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) "
             . "VALUES('$name', $value, 'no') "
             . "ON DUPLICATE KEY UPDATE `option_value` = GREATEST(`option_value`, VALUES(`option_value`))";

        // Exécute la requête (pas de prepare car on contrôle les paramètres)
        return $wpdb->query($sql);
    }
}