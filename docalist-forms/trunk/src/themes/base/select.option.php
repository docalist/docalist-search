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
$writer->startElement('option');
! is_null($value) && $writer->writeAttribute('value', $value);
$selected && $writer->writeAttribute('selected', 'selected');
$writer->text($label);
$writer->fullEndElement();