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
<option<?php
    ! is_null($value) && $this->htmlAttribute('value', $value);
    $selected && $this->htmlAttribute('selected', true) ?>><?php
    echo $label
?></option>