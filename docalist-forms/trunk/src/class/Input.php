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
 * Un champ input de type texte.
 *
 * Référence W3C :
 * {@link
 * http://www.w3.org/TR/html5/forms.html#text-(type=text)-state-and-search-state-(type=search)
 * input type=search}.
 *
 */
class Input extends Field {
    /**
     * @inheritdoc
     */
    protected $attributes = array('type' => 'text');
}
