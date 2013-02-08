<?php
/*
 * Affiche un groupe d'options pour un contrôle de type Choice.
 *
 * Ce template est appellé par choice.options.php avec les arguments suivants :
 *
 * - $label : le libellé à afficher pour le groupe d'options.
 * - $options : la liste des options de ce groupe.
 * - selected : la liste des options sélectionnés.
 */
?>
<p><?php echo $label ?></p><?php
$this->render($theme, 'optgroup', $args, true) ?>