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
use Exception;

/**
 * Classe de base des entités.
 *
 * Cette implémentation est basée sur ArrayObject.
 *
 * Les classes descendantes doivent surcharger la méthode loadSchema() pour
 * définir le schéma des entités.
 */
abstract class AbstractEntity extends ArrayObject implements EntityInterface {

    /**
     * Un cache contenant les schémas qu'on a déjà compilé.
     *
     * @var Schema[]
     */
    protected static $schemaCache;

    /**
     * Le schéma de l'entité
     *
     * @var Schema
     */
    protected $schema;

    /**
     * L'identifiant unique de l'entité.
     *
     * @var int
     */
    protected $id;

    /**
     * Construit une nouvelle entité à partir des données passées en paramètre.
     *
     * @param array $data Les données initiales de l'entité.
     */
    public function __construct(array $data = null) {
        // Configure l'objet ArrayAccess
        parent::setFlags(self::ARRAY_AS_PROPS);

        // Stocke les données en appellant offsetSet pour chaque champ
        if (!is_null($data)) {
            foreach ($data as $field => $value) {
                $this->offsetSet($field, $value);
            }
        }
    }

    public function id($id = null) {
        if (is_null($id)) {
            return $this->id;
        }

        $this->id = $id;
    }

    /*
     * public static function prototype() { return new static(); }
     */
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
        // Charge le schéma si cela n'a pas encore été fait
        if (is_null($this->schema)) {

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

            $this->schema = self::$schemaCache[$cacheKey];
        }

        // Retourne le schéma complet ou le schéma du champ indiqué
        return is_null($field) ? $this->schema : $this->schema->field($field);
    }

    /**
     * Retourne la valeur du champ indiqué.
     *
     * @param string $name
     *
     * @return mixed
     *
     * @throws Exception Si le champ indiqué n'existe pas dans le schéma de
     * l'entité.
     */
    public function offsetGet($name) {
        // Vérifie que le champ existe et récupère son schéma
        $field = $this->schema($name);

        // Si le champ n'est pas initialisé, stocke sa valeur par défaut
        if (!$this->offsetExists($name)) {
            parent::offsetSet($name, $field->instantiate($field->defaultValue()));
        }

        // Retourne la valeur
        return parent::offsetGet($name);
    }

    /**
     * Modifie la valeur du champ indiqué.
     *
     * @param string $name
     * @param string $value
     *
     * @throws Exception Si le champ indiqué n'existe pas dans le schéma de
     * l'entité.
     */
    public function offsetSet($name, $value) {
        // Vérifie que le champ existe et récupère son schéma
        $field = $this->schema($name);

        // Attribuer null à un champ équivaut à unset()
        if ($value === null) {
            $this->offsetUnset($name);

            return;
        }

        // Stocke la valeur
        parent::offsetSet($name, $field->instantiate($value));
    }

    /**
     * Retourne un tableau contenant les données actuelles de l'objet
     *
     * @return array
     */
    public function toArray() {
        $data = $this->getArrayCopy(); // $this ?
        foreach ($data as $name => $value) {
            if (is_scalar($value)) {
                $field = $this->schema($name);
                if ($value === $field->defaultValue()) {
                    unset($data[$name]);
                }
            } else {
                if (count($value) === 0) {
                    unset($data[$name]);
                } else {
                    $data[$name] = $value->toArray();
                }
            }
        }

        return $data;
    }
}