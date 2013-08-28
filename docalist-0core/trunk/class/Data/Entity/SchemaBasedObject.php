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
use ArrayObject, ArrayIterator;
use InvalidArgumentException;

/**
 * Implémentation de base de l'interface SchemaBasedObjectInterface.
 */
abstract class SchemaBasedObject implements SchemaBasedObjectInterface {

    /**
     * Un cache contenant les schémas qu'on a déjà compilé.
     *
     * @var Schema[]
     */
    protected static $schemaCache;

    protected $fields = array();

    /**
     * Construit un nouvel objet à partir des données passées en paramètre.
     *
     * @param array $data Les données initiales de l'objet.
     */
    public function __construct(array $data = null) {
        // Stocke les données en appellant offsetSet pour chaque champ
        if (!is_null($data)) {
            foreach ($data as $field => $value) {
                $this->__set($field, $value);
            }
        }
    }


    /**
     * Retourne la valeur du champ indiqué.
     *
     * @param string $name
     *
     * @return mixed
     *
     * @throws InvalidArgumentException Si le champ indiqué n'existe pas dans le schéma.
     */
    public function __get($name) {
        // retourne le champ s'il existe déjà
        if (array_key_exists($name, $this->fields)) {
            return $this->fields[$name];
        }

        // Vérifie que le champ existe et récupère son schéma
        $field = $this->schema($name);

        // Intialise le champ avec sa valeur par défaut
        return $this->fields[$name] = $field->instantiate($field->defaultValue());
    }

    /**
     * Modifie la valeur du champ indiqué.
     *
     * @param string $name
     * @param string $value
     *
     * @throws InvalidArgumentException Si le champ indiqué n'existe pas dans le schéma.
     */
    public function __set($name, $value) {
        // Vérifie que le champ existe et récupère son schéma
        $field = $this->schema($name);

        // Attribuer null à un champ équivaut à unset()
        $value === null && $value = $field->defaultValue();

        // Stocke la valeur
        $this->fields[$name] = $field->instantiate($value);
    }

    public function __unset($name) {
        $field = $this->schema($name);
        $this->fields[$name] = $field->instantiate($field->defaultValue());
    }

    public function __isset($name) {
        return isset($this->fields[$name]);
    }

    public function count() {
        return count($this->fields);
    }

    public function serialize() {
        return serialize($this->fields);
    }

    public function unserialize($serialized) {
        $this->fields = unserialize($serialized);
    }

    public function jsonSerialize() {
        return $this->fields;
    }

    public function getIterator() {
        return new ArrayIterator($this->fields);
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