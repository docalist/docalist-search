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
 * Un menu déroulant de type select.
 *
 * Référence W3C :
 * {@link http://www.w3.org/TR/html5/forms.html#the-select-element The select
 * element}.
 */
class Select extends Choice {

    /**
     * @var array Libellé et valeur de la première option du select lorsque
     * celui-ci n'est pas obligatoire (exemple : 'choisissez une valeur').
     */
    protected $firstOption = array(
        'label' => '…',
        'value' => ''
    );

    /**
     * Retourne ou modifie la valeur de l'attribut "multiple" du select.
     *
     * C'est juste un raccourci pour $select->attribute('multiple', 'multiple').
     *
     * @param bool $multiple
     *
     * @return bool|$this
     */
    public function multiple($multiple = null) {
        return $this->attribute('multiple', $multiple);
    }

    /**
     * Retourne ou modifier le libellé et la valeur de la première option
     * affichée dans le select lorsque celui-ci n'est pas obligatoire.
     *
     * Cette option n'est affichée que lorsque le select n'est pas obligatoire.
     *
     * Par défaut, une chaine vide est affichée (avec value=""). Vous pouvez
     * modifier l'option en appellant par exemple
     * <code>
     * $select->firstOption('Choisissez une valeur', 0);
     * </code>
     *
     * @param string $label Libellé à afficher
     * @param string $value Optionnel, valeur de l'option.
     *
     * @return array|$this Appellée sans paramètre, la méthode retourne un
     * tableau qui contient le libellé (clé label) et la valeur (clé value)
     * actuellement utilisés. Utilisée en setter, la méthode retourne $this.
     */
    public function firstOption($label = null, $value = '') {
        if (is_null($label)) {
            return $this->firstOption;
        }

        $this->firstOption = array(
            'label' => $label,
            'value' => $value
        );

        return $this;
    }

    /**
     * @inheritdoc
     *
     * Si le select est multivalué (multiple=true), il faut ajouter '[]' au
     * nom du contrôle.
     */
    protected function controlName() {
        $name = parent::controlName();
        $this->attribute('multiple') && $name .= '[]';

        return $name;
    }

    /**
     * @inheritdoc
     */
    protected function isArray() {
        return $this->attribute('multiple');
    }

}
