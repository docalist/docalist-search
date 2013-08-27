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

/**
 * L'interface Schema permet de décrire la liste des champs qui composent une
 * structure de données.
 */
interface SchemaInterface {

    /**
     * Retourne le nom.
     *
     * @return string
     */
    public function name();

    /**
     * Retourne le type.
     *
     * @return string
     */
    public function type();

    /**
     * Retourne la liste des champs.
     *
     * @return FieldInterface[]
     */
    public function fields();

    /**
     * Indique si le schéma contient le champ indiqué.
     *
     * @param string $field Le nom du champ à tester.
     *
     * @return bool
     */
    public function hasField($field);

    /**
     * Retourne le schéma du champ indiqué.
     *
     * @param string $field Le nom du champ.
     *
     * @throws Exception Une exception est générée si le champ indiqué n'existe
     * pas.
     *
     * @return FieldInterface
     */
    public function field($field);

    public function toArray();
}