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

use Docalist\Data\Schema\FieldInterface;
use ArrayObject;
use InvalidArgumentException;
use Docalist\Utils;
/**
 * Implémentation standard de l'interface PropertyInterface.
 *
 * Cette classe permet d'encapsuler :
 * - une propriété scalaire répétable (int[], string[], ...)
 * - un objet anonyme (sans entité associée)
 * - une collection d'objets anonymes (object[])
 * - une collection d'entités
 *
 * Les propriétés scalaires simples (non répétables) sont directement
 * représentée par le type php correspondant.
 */
class Property extends ArrayObject implements PropertyInterface {
    /**
     * Le schéma de la propriété.
     *
     * @var FieldInterface
     */
    protected $schema;

    /**
     * Construit une nouvelle propriété.
     *
     * @param FieldInterface $schema Le schéma de la propriété.
     *
     * @param array $data Les données initiales de la propriété.
     */
    public function __construct(FieldInterface $schema, array $data) {
        // Stocke le schéma
        $this->schema = $schema;

        // Configure l'objet ArrayAccess
        if (! $schema->repeatable()) {
            parent::setFlags(self::ARRAY_AS_PROPS);
        }

        // Stocke les données en appellant offsetSet pour chaque champ
        if (!is_null($data)) {
            foreach ($data as $field => $value) {
                $this->offsetSet($field, $value);
            }
        }
    }

    public function schema($field = null) {
        return is_null($field) ? $this->schema : $this->schema->field($field);
    }

    public function offsetGet($key) {
        // Si le champ est répétable, on fonctionne comme un tableau, key doit être un entier
        if ($this->schema->repeatable()) {
            if (! is_int($key)) {
                $msg = 'Bad index %s, expected int';
                throw new InvalidArgumentException(sprintf($msg, $key));
            }

            if (!$this->offsetExists($key)) {
                $msg = 'Index %s does not exists';
                throw new InvalidArgumentException(sprintf($msg, $key));

            }

            // Retourne le key-ème élément
            return parent::offsetGet($key);
        }

        // Objet simple, key doit être un nom de champ
        else {
            if (! is_string($key)) {
                $msg = 'Bad field name %s, expected string';
                throw new InvalidArgumentException(sprintf($msg, $key));
            }

            // Vérifie que le champ existe et récupère son schéma
            $field = $this->schema->field($key);

            // Si le champ n'est pas initialisé, stocke sa valeur par défaut
            if (!$this->offsetExists($key)) {
                parent::offsetSet($key, $field->instantiate($field->defaultValue()));
            }

            // Retourne la valeur
            return parent::offsetGet($name);
        }
    }

    /**
     * Modifie la valeur du champ indiqué.
     *
     * @param string $name
     * @param string $value
     *
     * @throws Exception Si le champ indiqué n'existe pas dans le schéma.
     */
    public function offsetSet($key, $value) {
        // Si le champ est répétable, on fonctionne comme un tableau, key doit être un entier
        if ($this->schema->repeatable()) {
            if (! is_int($key)) {
                $msg = 'Bad index %s, expected int';
                throw new InvalidArgumentException(sprintf($msg, $key));
            }
            $field = $this->schema;
            $single = true;
        }

        // Objet simple, key doit être un nom de champ
        else {
            if (! is_string($key)) {
                $msg = 'Bad field name %s, expected string';
                throw new InvalidArgumentException(sprintf($msg, $key));
            }

            // Vérifie que le champ existe et récupère son schéma
            $field = $this->schema($key);
            $single = false;
        }

        // Attribuer null à un champ équivaut à unset()
        if ($value === null) {
            parent::offsetUnset($key);

            return;
        }

        // Stocke la valeur
        parent::offsetSet($key, $field->instantiate($value, $single));
    }


//     /**
//      * Retourne un tableau contenant les données actuelles de la propriété.
//      *
//      * @return array
//      */
//     public function toArray() {
//         $data = $this->getArrayCopy(); // $this ?
//         foreach ($data as $name => $value) {
//             if (is_scalar($value)) {
//                 $field = $this->schema($name);
//                 if ($value === $field->defaultValue()) {
//                     unset($data[$name]);
//                 }
//             } else {
//                 if (count($value) === 0) {
//                     unset($data[$name]);
//                 } else {
//                     $data[$name] = $value->toArray();
//                 }
//             }
//         }

//         return $data;
//     }
}