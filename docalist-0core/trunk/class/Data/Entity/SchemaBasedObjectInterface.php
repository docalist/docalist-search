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

use Docalist\Data\Schema\SchemaInterface;
use Docalist\Data\Schema\FieldInterface;
use Countable, ArrayAccess, IteratorAggregate, Serializable, JsonSerializable;
use InvalidArgumentException;

/**
 * Interface d'un objet dont les données suivent un schéma prédéfini.
 *
 * Les champs de l'entité peuvent être manipulés comme s'il s'agissait de
 * propriétés de l'objet (e.g. $entity->title).
 *
 * Les propriétés de l'objet sont itérables : l'interface étend
 * IteratorAggregate
 *
 * Les propriétés de l'objet sont dénombrables : count($entity) retourne le
 * nombre de champs qui sont renseignés (contrairement à count($schema) qui
 * retourne le nombre de camps définis).
 *
 * L'objet est sérialisable
 */
interface SchemaBasedObjectInterface extends Countable, IteratorAggregate, Serializable, JsonSerializable {

    /**
     * Retourne le schéma de l'objet ou d'une propriété donnée de l'objet.
     *
     * @param string $property Optionnel. Le nom de la propriété à retourner.
     *
     * @return SchemaInterface FieldInterface sans paramètres, la
     * méthode retourne le schéma complet de l'objet. Si $field est fourni, la
     * méthode retourne le schéma du champ demandé.
     *
     * @throws InvalidArgumentException si la propriété demandée n'existe pas
     * dans le schéma de l'objet.
     */
    public function schema($property = null);

    // Interface Countable

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
    public function count();

    // Interface IteratorAggregate

    /**
     * Retourne un itérateur permettant d'énumérer les propriétés de l'objet.
     *
     * Cette méthode fait partie de l'interface {@link IteratorAggregate} qui
     * permet d'utiliser l'objet dans une boucle foreach.
     *
     * @return Iterator
     */
    public function getIterator();

    // Interface Serializable

    /**
     * Retourne une chaine contenant la version sérialisée de l'objet.
     *
     * Cette méthode fait partie de l'interface {@link Serializable}.
     *
     * @return string
     */
    public function serialize();

    /**
     * Construit un nouvel objet à partir des données sérialisées passées
     * en paramètre.
     *
     * Cette méthode fait partie de l'interface {@link Serializable}.
     *
     * @param serialized
     */
    public function unserialize($serialized);

    /**
     * Retourne les données à sérialiser lorsque json_encode() est appellée
     * sur cet objet.
     *
     * Cette méthode fait partie de l'interface {@link JsonSerializable}.
     *
     * @return string
     */
    public function jsonSerialize();

    /**
     * Retourne un tableau contenant les données de l'objet.
     *
     * @return array
     */
    public function toArray();
}