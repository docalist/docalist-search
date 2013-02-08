<?php
/**
 * This file is part of the "Docalist Forms" package.
 *
 * Copyright (C) 2012,2013 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Forms
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */

namespace Docalist\Forms;

/**
 * Classe de base pour les champs qui permettent à l'utilisateur de faire un
 * choix parmi une liste de valeurs possibles (select, checklist, radiolist).
 */
abstract class Choice extends Field {
    /**
     * @var array Les options proposées.
     */
    protected $options = array();

    /**
     * Retourne ou modifie la liste des options proposées.
     *
     * @param array $options
     *
     * @return array|$this
     */
    public function options(array $options = null) {
        if (is_null($options))
            return $this->options;

        $this->options = $options;

        return $this;
    }
}
