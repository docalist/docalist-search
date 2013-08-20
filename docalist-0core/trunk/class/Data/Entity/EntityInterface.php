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
 * Une entité est un objet structuré selon un schéma et qui dispose d'une
 * identité (il possède un identifiant unique).
 */
interface EntityInterface extends SchemaBasedInterface {

    /**
     * Retourne ou modifie l'identifiant unique de l'entité (sa clé).
     *
     * Appellée sans paramètre, id() retourne l'identifiant de l'entité ou une
     * valeur vide (empty) si l'entité n'a pas encore d'identifiant (null,
     * false, 0 ou '').
     *
     * Si un id est passé en paramètre, celui-ci devient le nouvel identifiant
     * de l'entité. L'identifiant ne peut être définit qu'une seule fois : une
     * exception est générée si vous essayez de changer l'id d'une entité qui a
     * déjà un identifiant non vide.
     *
     * @param string $id Optionnel, le nouvel identifiant de l'entité.
     *
     * @return scalar L'identifiant de l'entité.
     */
    public function id($id = null);
}