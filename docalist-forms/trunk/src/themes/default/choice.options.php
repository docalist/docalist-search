<?php
/**
 * Ce template sert à parcourir toutes les options d'un Choice et à faire
 * l'aiguillage entre les options simples et les groupes d'option.
 */

// Détermine les valeurs actuellement sélectionnées
$selected = array_flip((array)$this->data);

foreach ($this->options as $value => $label) {
    // Groupe d'options
    if (is_array($label)) {
        $this->render($theme, 'optgroup', array(
            'label' => $value,
            'options' => $label,
            'selected' => $selected,
        ));
    }

    // Option simple
    else {
        $this->render($theme, 'option', array(
            'value' => is_int($value) ? null : $value,
            'label' => $label,
            'selected' => isset($selected[is_int($value) ? $label : $value]),
        ));
    }
}
