<?php
/**
 * This file is part of the "Docalist Forms" package.
 *
 * Copyright (C) 2012-2014 Daniel Ménard
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

use Exception;

/**
 * Un Select qui permet à l'utilisateur de choisir une ou plusieurs valeurs
 * définies dans une table d'autorité.
 *
 * L'implémentation actuelle est basée sur selectize.
 *
 */
class TableLookup extends Select {
    protected $firstOption = false;
    protected $attributes = array('placeholder' => '…');
    protected $repeatable = false;

    protected $table;
    protected $valueField;
    protected $labelField;

    public function __construct($name, $table = null, $valueField = 'code', $labelField = 'label') {
        parent::__construct($name);

        $table && $this->table($table);
        $this->valueField = $valueField;
        $this->labelField = $labelField;
    }

    public function table($table = null) {
        if (is_null($table))
            return $this->table;

        if (! preg_match('~[a-z]+:[a-zA-Z0-9_-]+~', $table)) {
            throw new Exception('Nom de table incorrect. Format attendu "format:table".');
        }

        $this->table = $table;

        return $this;
    }

    public function valueField($valueField = null) {
        if (is_null($valueField))
            return $this->valueField;

        $this->valueField = $valueField;

        return $this;
    }

    public function labelField($labelField = null) {
        if (is_null($labelField))
            return $this->labelField;

        $this->labelField = $labelField;

        return $this;
    }

    protected static function getAssets() {
        return [
            [
                'type' => 'js',
                'name' => 'selectize.js',
            ],
            [
                'type' => 'css',
                'name' => 'selectize.css',
            ],

        ];
    }
}