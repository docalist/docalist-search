<?php
/*
 * Affiche une option d'un choice.
 *
 * Ce template est appellé par choice.options.php et par choice.optgroup.php
 * avec des paramètres :
 *
 * - $value : la valeur de l'option à générer
 * - $label : le libellé à afficher pour cette option
 * - selected : un booléen qui indique si l'option est sélectionnée ou non
 */
?>
<label><input<?php
    $this->htmlAttribute('name', $this->controlName());
    $this->htmlAttribute('type', 'checkbox');
    $this->htmlAttribute('value', is_null($value) ? $label : $value);
    $selected && $this->htmlAttribute('checked', true) ?>/><?php
    echo $label
?></label>