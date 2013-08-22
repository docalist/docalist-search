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
namespace Docalist\Data\Entity;

use Docalist\Data\Schema\Schema;
use ArrayObject;
use LogicException, Exception;

/**
 * Classe de base des entités.
 *
 * Les classes descendantes doivent surcharger la méthode loadSchema() pour
 * définir le schéma des entités.
 */
abstract class AbstractEntity extends SchemaBasedObject implements EntityInterface {

    /**
     * Un cache contenant les schémas qu'on a déjà compilé.
     *
     * @var Schema[]
     */
    protected static $schemaCache;

    /**
     * La clé primaire de l'entité.
     *
     * @var int
     */
    protected $primarykey;

    /**
     * Construit une nouvelle entité à partir des données passées en paramètre.
     *
     * @param array $data Les données initiales de l'entité.
     */
    public function __construct(array $data = null) {
        // Stocke les données en appellant offsetSet pour chaque champ
        if (!is_null($data)) {
            foreach ($data as $field => $value) {
                $this->__set($field, $value);
            }
        }
    }

    public function primarykey($primarykey = null) {
        // Setter
        if (! is_null($primarykey)) {
            // Vérifie que la primary key n'a pas déjà été définie
            if (! is_null($this->primarykey)) {
                $msg = 'Primary key already set';
                throw new LogicException($msg);
            }

            // Stocke la clé
            $this->primarykey = $primarykey;
        }

        // Getter
        return $this->primarykey;
    }

    /**
     * Initialise le schéma de l'entité.
     *
     * Les classes descendantes doivent surcharger cette méthode pour définir
     * le schéma de l'entité.
     *
     * @return array Schema classes descendantes peuvent retourner soit un
     * objet Schema, soit un tableau qui sera alors compilé avec la classe
     * Schema.
     */
    abstract protected function loadSchema();

    public function schema($field = null) {
        $cacheKey = get_class($this);

        // Pas encore dans $schemaCache, on le charge
        if (!isset(self::$schemaCache[$cacheKey])) {

            // Essaie de charger le schéma à partir du cache WordPress
            if (!WP_DEBUG) {
                if (false === $schema = wp_cache_get($cacheKey)) {
                    unset($schema);
                }
            }

            if (! isset($schema)) {

                // Demande à loadSchema() de définir le schéma
                $schema = $this->loadSchema();

                // Si on n'a toujours pas de schéma, erreur
                if (! $schema) {
                    $msg = 'No schema défined for entity %s. You must override loadSchema()';
                    throw new Exception(sprintf($msg, get_class($this)));
                }

                // Si loadSchema nous a retourné un tableau, on le compile
                if (is_array($schema)) {
                    $schema = new Schema($schema, get_class($this));
                }

                // Stocke le schéma en cache pour la prochaine fois
                if (!WP_DEBUG) {
                    wp_cache_add($cacheKey, $schema);
                }
            }

            // Stocke le schéma dans $schemaCache
            self::$schemaCache[$cacheKey] = $schema;
        }

        // Retourne le schéma complet ou le schéma du champ indiqué
        return is_null($field) ? self::$schemaCache[$cacheKey] : self::$schemaCache[$cacheKey]->field($field);
    }
}