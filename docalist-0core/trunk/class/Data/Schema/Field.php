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
namespace Docalist\Data\Schema;

use Docalist\Utils;
use Docalist\Data\Entity\Property;
use Docalist\Data\Entity\Collection;
use InvalidArgumentException;

/**
 * Implémentation standard de l'interface FieldInterface.
 */
class Field extends Schema implements FieldInterface {
    protected $type;
    protected $entity;
    protected $repeatable;
    protected $default;
    protected $label;
    protected $description;

    /**
     * Liste des types de champs reconnus et valeur par défaut.
     *
     * @var array
     */
    // @formatter:off
    protected static $fieldTypes = array(
        'string' => '',
        'int' => 0,
        'long' => 0,
        'bool' => false,
        'float' => 0.0,
        'object' => array(),
    );

    // @formatter:on

    public function __construct(array $data, $rootEntityClass) {
        // Teste si le champ contient des propriétés qu'on ne connait pas
        if ($unknown = array_diff_key($data, get_object_vars($this))) {
            $msg = 'Unknown field property(es) in field "%s": "%s"';
            $name = isset($data['name']) ? $data['name'] : '';
            throw new InvalidArgumentException(sprintf($msg, $name, implode(', ', array_keys($unknown))));
        }

        // Nom
        $this->setName(isset($data['name']) ? $data['name'] : null);

        // Type
        $default = isset($data['fields']) ? 'object' : 'string';
        $this->setType(isset($data['type']) ? $data['type'] : $default, $rootEntityClass);

        // Repeatable
        $this->setRepeatable(isset($data['repeatable']) ? $data['repeatable'] : $this->repeatable);

        // Default
        $this->setDefaultValue(isset($data['default']) ? $data['default'] : null);

        // Entity
        $this->setEntity(isset($data['entity']) ? $data['entity'] : null, $rootEntityClass);

        // Label
        $this->setLabel(isset($data['label']) ? $data['label'] : null);

        // Description
        $this->setDescription(isset($data['description']) ? $data['description'] : null);

        // Fields
        $this->setFields(isset($data['fields']) ? $data['fields'] : null, $rootEntityClass);

    }

    protected function setName($name) {
        // Le nom du champ est obligatoire
        if (empty($name)) {
            $msg = 'Field must have a name';
            throw new InvalidArgumentException(sprintf($msg));
        }

        // Le nom de champ ne doit contenir que des lettres
        if (!ctype_alpha($name)) {
            $msg = 'Invalid field name "%s": must contain only letters';
            throw new InvalidArgumentException(sprintf($msg, $name));
        }

        $this->name = $name;
    }

    public function name() {
        return $this->name;
    }

    protected function setType($type, $rootEntityClass) {
        // type='xx*' équivaut à type='xx' + repeatable=true
        if (substr($type, -1) === '*') {
            $type = substr($type, 0, -1);
            $this->repeatable = true;
        }

        // Teste s'il s'agit d'un type simple (int, string, etc.)
        if (array_key_exists($type, self::$fieldTypes)) {
            $this->type = $type;

            return;
        }

        // Type est une entité
        $this->type = 'object';
        $this->setEntity($type, $rootEntityClass);
    }

    public function type() {
        return $this->type;
    }

    protected function setRepeatable($repeatable) {
        $this->repeatable = (bool) $repeatable;
    }

    public function repeatable() {
        return $this->repeatable;
    }

    protected function setDefaultValue($default) {
        if (! is_null($default)) {
            if ($this->type === 'object') {
                if ($ok = is_array($default)) {
                    $keys = array_keys($default);
                    if ($this->repeatable) {
                        // on attend une liste d'entités
                        $expected = 'array of entities (array of numerical arrays)';
                        $ok = count(array_filter($keys, 'is_int')) === count($keys);
                    } else {
                        // on attend une liste de champs
                        $expected = 'entity (associative array)';
                        $ok = count(array_filter($keys, 'is_string')) === count($keys);
                    }
                }
            } else {
                $is = "is_" . $this->type;
                if ($this->repeatable) {
                    $expected = 'numerical array of ' . $this->type;
                    // on doit avoir un tableau
                    if ($ok = is_array($default)) {

                        // Les clés doivent être numériques
                        $keys = array_keys($default);
                        $ok = count(array_filter($keys, 'is_int')) === count($keys);

                        // Toutes les valeurs doivent être du même type que le champ
                        $ok && $ok = count(array_filter($default, $is)) === count($keys);
                    }
                } else {
                    $expected = $this->type;
                    $ok = $is($default);
                }
            }

            if (! $ok) {
                $msg = 'Bad default value for field "%s": expected %s';
                throw new InvalidArgumentException(sprintf($msg, $this->name, $expected));
            }
        }
        $this->default = $default;
    }

    public function defaultValue() {
        if (!is_null($this->default)) {
            return $this->default;
        }

        return $this->repeatable ? array() : self::$fieldTypes[$this->type];
    }

    protected function setEntity($entity, $rootEntityClass) {
        if (empty($entity)) {
            if ($this->type === 'object') {
                $entity = 'Docalist\Data\Object';
            }
            return;
        }

        // Seuls les champs objets peuvent avoir une entité
        if ($this->type !== 'object') {
            $msg = 'Field "%s" can not have an entity property: not an object';
            throw new InvalidArgumentException(sprintf($msg, $this->name));
        }

        // Vérifie que la classe indiquée existe
        if (! class_exists($entity)) {
            // Nom de classe relatif au namespace en cours ?
            $class = Utils::ns($rootEntityClass) . '\\' . $entity;
            if (! class_exists($class)) {
                $msg = 'Invalid entity type "%s" for field "%s": class not found';
                throw new InvalidArgumentException(sprintf($msg, $entity, $this->name));
            }
            $entity = $class;
        }

        // Vérifie que la classe est une entité
        if (! is_a($entity, 'Docalist\Data\Entity\EntityInterface', true)) {
            $msg = 'Invalid entity type "%s" for field "%s": not an EntityInterface';
            throw new InvalidArgumentException(sprintf($msg, $entity, $this->name));
        }

        $this->entity = $entity;
    }

    public function entity() {
        return $this->entity;
    }

    protected function setLabel($label) {
        $this->label = $label;
    }

    public function label() {
        return $this->label ?: $this->name;
    }

    public function description() {
        return $this->description;
    }

    protected function setDescription($description) {
        $this->description = $description;
    }

    protected function setFields(array $fields = null, $rootEntityClass) {
        if ($fields && $this->type !== 'object') {
            $msg = 'Field "%s" can not have fields: not an object';
            throw new InvalidArgumentException(sprintf($msg, $this->name));
        }

        if ($fields && $this->entity) {
            $msg = 'Field "%s" can not have fields: fields are already defined by entity type';
            throw new InvalidArgumentException(sprintf($msg, $this->name));
        }

        if ($this->type === 'object' && empty($this->entity)) {
            if (empty($fields)) {
                $msg = 'No fields defined for field "%s"';
                throw new InvalidArgumentException(sprintf($msg, $this->name));
            }
            parent::setFields($fields, $rootEntityClass);
        }
    }

    public function fields() {
        return $this->fields;
    }

    public function field($field) {
        if (!isset($this->fields[$field])) {
            $msg = 'Field "%s" does not exist';
            throw new InvalidArgumentException(sprintf($msg, $field));
        }

        // @todo : vérifier que type = object ou document

        return $this->fields[$field];
    }

    public function toArray() {
        $result = array();
        foreach ($this as $name => $value) {
            if (is_null($value) || $value === false) {
                continue;
            }

            if ($name === 'fields') {
                if ($value) {
                    $result['fields'] = array();
                    foreach ($value as $field) {
                        $result['fields'][] = $field->toArray();
                    }
                }
            } else {
                $result[$name] = $value;
            }
        }

        return $result;
    }

    public function instantiate($value = null, $single = false) {
        is_null($value) && $value = $this->defaultValue();

        if (! $single && $this->repeatable) {
            return new Collection($this, $value);
        }

        if ($this->type === 'object') {
            // Une entité
            if ($this->entity) {
                $value = new $this->entity($value);
            }

            // Un objet anonyme
            else {
                $value = new Property($this, $value);
            }
        }

        return $value;
    }
}