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
 * Un "bout" de formulaire.
 */
class Fragment extends Fields {
    /**
     * @inheritdoc
     */
    protected $descriptionAfter = false;

    /**
     * Crée un nouveau bout de formulaire.
     *
     * @param string $name Le nom du champ.
     */
    public function __construct($name = null) {
        parent::__construct($name);
    }
}
