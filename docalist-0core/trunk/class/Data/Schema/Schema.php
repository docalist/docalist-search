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

use InvalidArgumentException;

/**
 * Implémentation standard de l'interface SchemaInterface.
 */
class Schema implements SchemaInterface {
    protected $name;
    protected $fields;

    /**
     * Construit un nouveau schéma.
     *
     * @param array $data La liste des champs du schéma.
     *
     * @param string $entity Nom complet de la classe de l'entité à laquelle
     * s'applique ce schéma.
     *
     * @throws InvalidArgumentException Si le schéma contient des erreurs.
     */
    public function __construct(array $fields = array(), $entity) {
        $this->name = $entity;
        $this->setFields($fields, $entity);
    }

    protected function setFields(array $fields = null, $entity) {
        if (empty($fields)) {
            $msg = "No fields defined";
            throw new InvalidArgumentException($msg);
        }
        $this->fields = array();
        foreach ($fields as $key => $field) {
            // Gère les raccourcis autorisés si $field est une chaine
            if (is_string($field)) {
                // Champ de la forme entier => nom
                if (is_int($key)) {
                    $field = array('name' => $field);
                }

                // Champ de la forme nom => type
                else {
                    $field = array('name' => $key, 'type' => $field);
                }
            }

            // Le nom peut être indiqué comme clé ou comme propriété mais pas les deux
            elseif (is_string($key)) {
                if (isset($field['name'])) {
                    $msg = 'Field name defined twice: %s,%s';
                    throw new InvalidArgumentException(sprintf($msg, $key, $field['name']));
                }
                $field['name'] = $key;
            }

            // Compile
            $field = new Field($field, $entity);

            // Vérifie que le nom du champ est unique
            if (isset($this->fields[$field->name])) {
                $msg = 'Field %s defined twice';
                throw new InvalidArgumentException(sprintf($msg, $field->name));
            }

            // Stocke le champ
            $this->fields[$field->name()] = $field;
        }
    }

    public function name() {
        return $this->name;
    }

    public function type() {
        return 'schema';
    }

    public function fields() {
        return $this->fields;
    }

    public function field($field) {
        if (!isset($this->fields[$field])) {
            $msg = 'Field %s does not exist';
            throw new InvalidArgumentException(sprintf($msg, $field));
        }

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
}