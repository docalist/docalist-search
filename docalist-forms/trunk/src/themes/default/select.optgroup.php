<?php
/*
 * Affiche un groupe d'options pour un choice.
 *
 * Ce template est appellé par choice.options.php avec des paramètres :
 *
 * - $label : le libellé à afficher pour le groupe d'options.
 * - $options : la liste des options de ce groupe.
 * - selectedOptions : la liste des options sélectionnés.
 */
?>
<optgroup<?php $this->htmlAttribute('label', $label) ?>><?php
    $this->render($theme, 'optgroup', $args, true)
?></optgroup>