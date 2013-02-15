<?php
/*
 * Affiche un groupe d'options pour un choice.
 *
 * Ce template est appellé par choice.options.php avec des paramètres :
 *
 * - $label : le libellé à afficher pour le groupe d'options.
 * - $options : la liste des options de ce groupe.
 * - selected : la liste des options sélectionnés.
 */
foreach ($options as $value => $label) {
    $this->render($theme, 'option', array(
        'value' => is_int($value) ? null : $value,
        'label' => $label,
        'selected' => isset($selected[is_int($value) ? $label : $value]),
    ) + $args);
}
