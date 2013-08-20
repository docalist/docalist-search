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

/**
 * Interface d'un dépôt dans lequel on peut stocker des entités.
 */
interface RepositoryInterface {

    /**
     * Retourne le type des entités gérées par ce dépôt.
     *
     * @return string Le nom complet de la classe PHP utilisée pour représenter
     * les entités.
     */
    public function type();

    /**
     * Charge une entité depuis le dépôt.
     *
     * @param scalar $key La clé de l'entité à charger.
     *
     * @param string $type Optionnel. En général, toutes les entités d'un dépôt
     * ont le même type : celui retourné par la méthode type(). Cependant, dans
     * certains cas, on souhaite obtenir une sous-classe du type indiqué. Dans
     * ce cas, on peut indiquer ici le nom de la classe à utiliser (il doit
     * s'agir d'une classe descendante du type retourné par type()).
     *
     * @return EntityInterface
     */
    public function load($key, $type = null);

    /**
     * Enregistre une entité dans le dépôt.
     *
     * Si l'entité existe déjà dans le dépôt (i.e. elle a déjà une clé), elle
     * est mise à jour. Dans le cas contraire, l'entité est ajoutée dans le
     * dépôt et sa clé est initialisée.
     *
     * @param EntityInterface $entity L'entité à enregistrer.
     *
     * @throws Exception Si l'entité n'est pas du bon type ou si une erreur
     * survient durant l'enregistrement des données.
     */
    public function store(EntityInterface $entity);

    /**
     * Supprime une entité existante du dépôt.
     *
     * @param scalar|EntityInterface $entity La clé ou l'entité à détruire.
     */
    public function delete($entity);
}