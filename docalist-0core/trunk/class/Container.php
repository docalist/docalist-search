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
 * Remarque : Container ne devrait pas être une interface mais un Trait.
 * Il faudra refactorer le code quand on aura migré en php5.4 (rechercher la
 * chaine "TraitContainer" dans le code).
 */

/**
 * Représente une collection d'objets Registrable auxquels on accède via
 * leur nom.
 */
interface Container {
    /**
     * Retourne l'objet dont le nom est passé en paramètre.
     *
     * @param string $name Le nom de l'objet à retourner.
     *
     * @return Registrable
     *
     * @throws Exception si l'objet demandé n'existe pas dans la collection.
     */
    public function get($name);

    /**
     * Ajoute un objet nommé à la collection et appelle sa méthode register().
     *
     * @param Registrable $object L'objet à ajouter.
     *
     * @throws Exception Si le conteneur contient déjà un objet ayant le
     * même nom que l'objet à ajouter.
     *
     * @return Container $this.
     */
    public function add(Registrable $object);
}
