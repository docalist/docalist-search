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

/**
 * Une entité est un SchemaBasedObject qui peut être stocké dans un Repository
 * et qui dispose d'une clé primaire.
 */
interface EntityInterface extends SchemaBasedObjectInterface {

    /**
     * Retourne ou modifie la clé primaire de l'entité.
     *
     * Appellée sans paramètre, la méthode retourne la clé primaire de l'entité
     * ou une valeur vide (empty) si l'entité n'a pas encore été enregistrée
     * dans un dépôt..
     *
     * Si un paramètre est fourni, celui-ci devient la clé primaire de l'entité.
     *
     * @param scalar $primarykey Optionnel, la clé primaire à affecter à
     * l'entité.
     *
     * @throws LogicException La clé primaire ne peut être définie qu'une seule
     * fois : une exception est générée si vous essayez de changer la clé
     * primaire d'une entité qui a déjà été enregistrée dans un dépôt.
     *
     * @return scalar La clé primaire de l'entité ou vide s'il s'agit d'une
     * nouvelle entité.
     */
    public function primaryKey($primarykey = null);
}