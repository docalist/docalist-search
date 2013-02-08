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
 * Une case à cocher.
 *
 * Référence W3C :
 * {@link http://www.w3.org/TR/html5/forms.html#checkbox-state-(type=checkbox)
 * input type=checkbox}.
 */
class Checkbox extends Input {
    /**
     * @inheritdoc
     */
    protected $attributes = array(
        'type' => 'checkbox',
        'value' => 1
    );
}
