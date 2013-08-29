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
    use RegistrableTrait;

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
     * Ajoute un objet enregistrable à la collection et appelle sa méthode
     * register().
     *
     * @param Registrable $registrable L'objet à ajouter.
     *
     * @throws Exception Si le conteneur contient déjà un objet ayant le
     * même nom que l'objet à ajouter.
     *
     * @return Container $this.
     */
    public function add(RegistrableInterface $registrable) {
        // Indique au registrable que nous comme son container
        $registrable->parent($this);

        // Récupère son ID et vérifie qu'il est unique
        $id = $registrable->id();
        if (isset($this->items[$id])) {
            $msg = __('Il existe déjà un objet %s dans %s', 'docalist-core');
            throw new Exception(sprintf($msg, $id, $this->id()));
        }

        // Ajoute l'objet dans la collection
        $this->items[$id] = $registrable;

        // Enregistre l'objet
        $registrable->register();

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
}
