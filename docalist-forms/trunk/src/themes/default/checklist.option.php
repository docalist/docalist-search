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
$writer->startElement('label');

$writer->startElement('input');
$writer->writeAttribute('name', $this->controlName());
$writer->writeAttribute('type', 'checkbox');
$writer->writeAttribute('value', is_null($value) ? $label : $value);
$selected && $writer->writeAttribute('checked', 'checked');
$writer->endElement();

$writer->text($label);
$writer->fullEndElement();