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
 * Une liste de cases à cocher.
 */
class Checklist extends Choice {
    /**
     * @inheritdoc
     */
    protected $descriptionAfter = false;

    /**
     * @inheritdoc
     *
     * Une checklist est obligatoirement multivaluée (et indépendemment de ça,
     * elle peut être repeatable). Le nom du contrôle a toujours '[]' à la fin.
     */
    protected function controlName() {
        return parent::controlName() . '[]';
    }

}
