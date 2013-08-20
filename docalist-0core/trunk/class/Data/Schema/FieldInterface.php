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
 * Description d'un champ au sein d'un schéma.
 */
interface FieldInterface extends SchemaInterface {
    /**
     * Indique si le champ est répétable.
     *
     * @return bool
     */
    public function repeatable();

    /**
     * Retourne la valeur par défaut du champ.
     *
     * @return mixed
     */
    public function defaultValue();

    /**
     * Indique le nom de la classe à utiliser pour représenter les données du
     * champ.
     *
     * @return string
     */
    public function entity();

    /**
     * Retourne le libellé du champ, ou son nom si le champ n'a pas de libellé.
     *
     * @return string
     */
    public function label();

    /**
     * Retourne la description du champ.
     *
     * @return string
     */
    public function description();

    /**
     * Crée une nouvelle instance du champ.
     *
     * @param scalar|array $value
     *
     * @return scalar|Property
     */
    public function instantiate($value);
}