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
 * Un champ input de type hidden.
 *
 * Référence W3C :
 * {@link http://www.w3.org/TR/html5/forms.html#the-fieldset-element The
 * fieldset element}.
 */
class Fieldset extends Fragment {
    /**
     * Crée un nouveau Fieldset.
     *
     * @param string $label Le libellé (la légende) du fieldset.
     */
    public function __construct($label = null) {
        parent::__construct();
        $this->label = $label;
    }

}
