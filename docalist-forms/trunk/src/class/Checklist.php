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

    protected $noBrackets = false;

    /**
     * @inheritdoc
     *
     * Une checklist est obligatoirement multivaluée (et indépendemment de ça,
     * elle peut être repeatable). Le nom du contrôle a toujours '[]' à la fin.
     */
    protected function controlName() {
        return $this->noBrackets ? parent::controlName() : parent::controlName() . '[]';
    }

    public function noBrackets($noBrackets = null) {
        if (is_null($noBrackets)) {
            return $this->noBrackets;
        }
        $this->noBrackets = (bool) $noBrackets;

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function isArray() {
        return true;
    }

    public function repeatable($repeatable = null) {
        if (is_null($repeatable)) {
            return false;
        }
        return parent::repeatable($repeatable);
    }
}
