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
 * Une propriété répétable.
 */
class Collection implements SchemaBasedObjectInterface, ArrayAccess {
    /**
     * Le schéma des éléments de la collection.
     *
     * @var FieldInterface
     */
    protected $schema;

    /**
     * Les données de la collection
     *
     * @var scalar|Property
     */
    protected $items = array();

    /**
     * Construit une nouvelle collection.
     *
     * @param FieldInterface $schema Le schéma de la collection.
     *
     * @param array $data Les données initiales de la collection.
     */
    public function __construct(FieldInterface $schema, array $data = null) {
        // Stocke le schéma
        $this->schema = $schema;

        // Stocke les données
        if (! is_null($data)) {
            foreach($data as $item) {
                $this->items[] = $schema->instantiate($item, true);
            }
        }
    }

    public function schema($field = null) {
        return is_null($field) ? $this->schema : $this->schema->field($field);
    }

    public function count() {
        return count($this->items);
    }

    public function getIterator() {
        return new ArrayIterator($this->items);
    }

    public function serialize() {
        return serialize($this->items);
    }

    public function unserialize($serialized) {
        $this->items = unserialize($serialized);
    }

    public function jsonSerialize() {
        return $this->items;
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

    public function toArray() {
        return array_map(function($item) {
            return is_scalar($item) ? $item : $item->toArray();
        }, $this->items);
    }

    public function __toString() {
        // Collection d'objets
        if ($this->schema->type() ==='object') {
            return implode('<br />', $this->items);
        }

        // Collection de scalaires
        return implode(' ¤ ', $this->items);
    }

}