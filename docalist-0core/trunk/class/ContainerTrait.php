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
 * Implémentation standard de l'interface {@link ContainerInterface}.
 */
trait ContainerTrait {
    /**
     * @var array Liste des objets présents dans ce containeur.
     */
    protected $items = array();

    /**
     * Indique si la collection contient l'objet dont le nom est passé en
     * paramètre.
     *
     * @param string $name Le nom de l'objet recherché.
     *
     * @return bool
     */
    public function has($name) {
        return isset($this->items[$name]);
    }

    /**
     * Retourne l'objet dont le nom est passé en paramètre.
     *
     * @param string $name Le nom de l'objet à retourner.
     *
     * @return Registrable
     *
     * @throws Exception si l'objet demandé ne figure pas dans le containeur.
     */
    public function get($name) {
        if (!isset($this->items[$name])) {
            $msg = __('Aucun objet %s dans ce container', 'docalist-core');
            throw new Exception(sprintf($msg, $name));
        }

        return $this->items[$name];
    }

    /**
     * Ajoute un objet enregistranle à la collection et appelle sa méthode
     * register().
     *
     * @param Registrable $object L'objet à ajouter.
     *
     * @throws Exception Si le conteneur contient déjà un objet ayant le
     * même nom que l'objet à ajouter.
     *
     * @return Container $this.
     */
    public function add(Registrable $object) {
        $name = $object->name();
        if (isset($this->items[$name])) {
            $msg = __('Il existe déjà un objet %s dans ce container', 'docalist-core');
            throw new Exception(sprintf($msg, $name));
        }

        // Ajoute l'objet dans la collection
        $object->parent($this);
        $this->items[$name] = $object;

        // Enregistre l'objet
        $object->register();

        return $this;
    }

    /**
     * Retourne la liste des items
     *
     * @return Registrable[]
     */
    public function items() {
        return $this->items;
    }

	// La méthode Registrable::plugin() appelle $this->parent->plugin()
	// On doit donc garantir qu'un container a toujours une méthode plugin()
	// Pour cela, il faut que la méthode figure dans l'interface.
	// A revoir quand on passera aux traits.
	// public function plugin() {}
}
