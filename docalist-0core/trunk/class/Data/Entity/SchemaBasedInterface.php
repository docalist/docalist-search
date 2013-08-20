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
use Countable, ArrayAccess, IteratorAggregate, Serializable;
use InvalidArgumentException;

/**
 * Interface d'un objet dont les données suivent un schéma prédéfini.
 *
 * Les propriétés de l'objet peuvent être manipulées de plusieurs façons :
 * - comme champs de l'objet : $entity->title
 * - comme éléments de tableau : $entity['title']
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
interface SchemaBasedInterface extends Countable, ArrayAccess, IteratorAggregate, Serializable {

    /**
     * Retourne le schéma de l'objet ou d'une propriété donnée de l'objet.
     *
     * @param string $property Optionnel. Le nom de la propriété à retourner.
     *
     * @return SchemaInterface|FieldInterface Appellée sans paramètres, la
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

    // Interface ArrayAccess

    /**
     * Indique si la propriété indiquée existe.
     *
     * Cette méthode fait partie de l'interface {@link ArrayAccess} qui permet
     * d'accéder aux propriétés de l'objet comme s'il s'agissait d'un tableau.
     *
     * @param string $property Le nom de la propriété à tester.
     *
     * @return bool Retourne true si la propriété indiquée est définie dans le
     * schéma, false sinon.
     */
    public function offsetExists($property);

    /**
     * Retourne la valeur actuelle de la propriété indiquée.
     *
     * Cette méthode fait partie de l'interface {@link ArrayAccess} qui permet
     * d'accéder aux propriétés de l'objet comme s'il s'agissait d'un tableau.
     *
     * @param string $property Le nom de la propriété à retourner.
     *
     * @throws InvalidArgumentException Si la propriété indiquée n'existe pas
     * dans le schéma.
     *
     * @return scalar|Property
     */
    public function offsetGet($property);

    /**
     * Modifie la valeur de la propriété indiquée.
     *
     * Cette méthode fait partie de l'interface {@link ArrayAccess} qui permet
     * d'accéder aux propriétés de l'objet comme s'il s'agissait d'un tableau.
     *
     * Remarque : si $value vaut null, la méthode se comporte comme si
     * offsetUnset avait été appellé.
     *
     * @param string $property Le nom de la propriété à modifier.
     *
     * @param scalar|array $value La nouvelle valeur de la propriété.
     *
     * @throws InvalidArgumentException Si la propriété indiquée n'existe pas
     * dans le schéma.
     */
    public function offsetSet($property, $value);

    /**
     * Supprime la propriété indiquée.
     *
     * Cette méthode fait partie de l'interface {@link ArrayAccess} qui permet
     * d'accéder aux propriétés de l'objet comme s'il s'agissait d'un tableau.
     *
     * La propriété n'est pas réellement supprimée : la valeur existante si elle
     * existe est effacée et la propriété retrouve sa valeur par défaut telle
     * que définie dans le schéma.
     *
     * @param string $property Le nom de la propriété à supprimer.
     *
     * @throws InvalidArgumentException Si la propriété indiquée n'existe pas
     * dans le schéma.
     */
    public function offsetUnset($property);

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
}