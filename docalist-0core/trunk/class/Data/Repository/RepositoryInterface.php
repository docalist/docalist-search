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
namespace Docalist\Data\Repository;

use Docalist\Data\Entity\EntityInterface;

use InvalidArgumentException;

/**
 * Interface d'un dépôt dans lequel on peut stocker des entités.
 */
interface RepositoryInterface {

    /**
     * Retourne le type des entités gérées par ce dépôt.
     *
     * @return string Le nom complet de la classe PHP utilisée pour représenter
     * les entités de ce dépôt.
     */
    public function type();

    /**
     * Charge une entité depuis le dépôt.
     *
     * @param scalar|EntityInterface $entity L'entité à charger.
     *
     * @param null|string|false $type Optionnel. Le type des données à
     * retourner.
     *
     * Par défaut (quand type vaut null), load() retourne un objet entité ayant
     * le type du dépôt (i.e. le nom de classe retourné par la méthode type()).
     *
     * Vous pouvez obtenir une entité d'un type différent en passant dans $type
     * le nom d'une classe descendante du type du dépôt.
     *
     * Enfin, il est possible de récupérer les données brutes de l'entité en
     * passant false en paramètre.
     *
     * @throws InvalidArgumentException Si l'entité ne peut pas être chargée
     * ou si le nom de classe indiqué dans $type n'est pas correct.
     *
     * @return EntityInterface|array Retourne un objet entité ou un tableau si
     * false a été passé en paramètre pour $type.
     */
    public function load($entity, $type = null);

    /**
     * Enregistre une entité dans le dépôt.
     *
     * Si l'entité existe déjà dans le dépôt (i.e. elle a déjà une clé), elle
     * est mise à jour. Dans le cas contraire, l'entité est ajoutée dans le
     * dépôt et sa clé est initialisée.
     *
     * @param EntityInterface $entity L'entité à enregistrer.
     *
     * @throws InvalidArgumentException Si l'entité n'est pas du bon type ou
     * si une erreur survient durant l'enregistrement des données.
     */
    public function store(EntityInterface $entity);

    /**
     * Supprime une entité existante du dépôt.
     *
     * @param scalar|EntityInterface $entity La clé ou l'entité à détruire.
     */
    public function delete($entity);
}