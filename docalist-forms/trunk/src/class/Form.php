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
 * Un formulaire.
 *
 * Référence W3C :
 * {@link http://www.w3.org/TR/html5/forms.html#the-form-element The form
 * element}.
 */
class Form extends Fields {
    /**
     * Crée un nouveau formulaire.
     *
     * @param string $action L'action du formulaire.
     * @param string $method La méthode du formulaire : "get" ou "post", post
     * par défaut.
     */
    public function __construct($action = '', $method = 'post') {
        $this->attributes = array(
            'action' => $action,
            'method' => $method
        );
    }

}
