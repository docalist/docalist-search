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

/**
 * Représente une nouvelle taxonomie personnalisée WordPress
 */
abstract class Taxonomy implements RegistrableInterface {
    use RegistrableTrait;

    /**
     * @var string|string[] Liste des post types auxquels s'applique la
     * taxonomie.
     *
     * (second paramètre de la fonction WordPress register_taxonomy()).
     */
    protected $postTypes;

    /**
     * {@inheritdoc}
     */
    public function register() {
        register_taxonomy($this->id(), $this->postTypes, $this->options());
    }

    /**
     * Retourne le post type ou la liste des post types auxquels est rattachée
     * la taxonomie.
     *
     * @return string|string[] Une chaine ou un tableau qui sera passé comme
     * second paramètre lors de l'appel à la fonction register_taxonomy() de
     * WordPress.
     */
    public function postTypes() {
        return $this->postTypes;
    }

    /**
     * Retourne les options à utiliser pour définir cette taxonomie dans
     * wordpress.
     *
     * @return array les arguments à passer à la fonction register_taxonomy()
     * de WordPress (troisième paramètre).
     */
    abstract protected function options();
}
