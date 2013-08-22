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
use ArrayAccess;
use ArrayIterator;

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
 *
 * @todo : faire une interface CollectionInterface (pour les tests, entre autres)
 */
class Collection implements SchemaBasedObjectInterface, ArrayAccess {
    /**
     * Le schéma de la propriété.
     *
     * @var FieldInterface
     */
    protected $schema;

    protected $items = array();

    /**
     * Construit une nouvelle propriété.
     *
     * @param FieldInterface $schema Le schéma de la propriété.
     *
     * @param array $data Les données initiales de la propriété.
     */
    public function __construct(FieldInterface $schema, array $data = null) {
        // Stocke le schéma
        $this->schema = $schema;

        if (! is_null($data)) {
            foreach($data as $item) {
                $this->items[] = $schema->instantiate($item, true);
            }
        }
    }

    public function schema($field = null) {
        return is_null($field) ? $this->schema : $this->schema->field($field);
    }

    /**
     * Retourne le nombre de propriétés.
     *
     * Cette méthode fait partie de l'interface {@link Countable} qui permet
     * d'utiliser la fonction count().
     *
     * La méthode retourne le nombre de champs qui sont renseignés au sein de
     * l'objet (contrairement à la méthode count de la classe {@link Schema}
     * qui retourne le nombre de champs définis).
     */
    public function count() {
        return count($this->items);
    }


    // Interface IteratorAggregate

    /**
     * Retourne un itérateur permettant d'énumérer les propriétés de l'objet.
     *
     * Cette méthode fait partie de l'interface {@link IteratorAggregate} qui
     * permet d'utiliser l'objet dans une boucle foreach.
     *
     * @return Iterator
    */
    public function getIterator() {
        return new ArrayIterator($this->items);
    }

    // Interface Serializable

    /**
     * Retourne une chaine contenant la version sérialisée de l'objet.
     *
     * Cette méthode fait partie de l'interface {@link Serializable}.
     *
     * @return string
    */
    public function serialize() {
        return serialize($this->items);
    }

    /**
     * Construit un nouvel objet à partir des données sérialisées passées
     * en paramètre.
     *
     * Cette méthode fait partie de l'interface {@link Serializable}.
     *
     * @param serialized
    */
    public function unserialize($serialized) {
        $this->items = unserialize($serialized);
    }

    public function offsetExists ($offset) {
        return isset($this->items[$offset]);
    }
    public function offsetGet ($offset) {
        return $this->items[$offset];
    }
    public function offsetSet ($offset, $value) {
        if (is_null($offset)) {
            $this->items[] = $this->schema->instantiate($value, true);
        } else {
            $this->items[$offset] = $this->schema->instantiate($value, true);
        }
    }

    public function offsetUnset($offset) {
        $this->schema->instantiate(null, true);
    }

}