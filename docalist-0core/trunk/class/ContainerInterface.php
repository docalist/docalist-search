<?php
/**
 * This file is part of the "Docalist Core" plugin.
 *
 * Copyright (C) 2012, 2013 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */

namespace Docalist;
use Exception;

/**
 * Représente un container.
 *
 * Un container est un registrable {@link RegistrableInterface enregistrable}
 * qui peut lui-même contenir d'autres objets enregistrables
 * (c'est une collection).
 */
interface ContainerInterface extends RegistrableInterface {
    /**
     * Indique si la collection contient l'objet dont le nom est passé en
     * paramètre.
     *
     * @param string $name Le nom de l'objet recherché.
     *
     * @return bool
     */
    public function has($name);

    /**
     * Retourne l'objet dont le nom est passé en paramètre.
     *
     * @param string $name Le nom de l'objet à retourner.
     *
     * @return RegistrableInterface
     *
     * @throws Exception si l'objet demandé n'existe pas dans la collection.
     */
    public function get($name);

    /**
     * Ajoute un objet nommé à la collection et appelle sa méthode register().
     *
     * @param RegistrableInterface $registrable L'objet à ajouter.
     *
     * @throws Exception Si le conteneur contient déjà un objet ayant le
     * même nom que l'objet à ajouter.
     *
     * @return ContainerInterface $this.
     */
    public function add(RegistrableInterface $registrable);

    /**
     * Retourne la liste des items
     *
     * @return RegistrableInterface[]
     */
    public function items();
}
