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
$writer->startElement('li');
$writer->startElement('label');
self::$indent && $writer->setIndent(false);
$writer->startElement('input');
$writer->writeAttribute('name', $this->controlName());
$writer->writeAttribute('type', 'checkbox');
$writer->writeAttribute('value', is_null($value) ? $label : $value);
$selected && $writer->writeAttribute('checked', 'checked');
$writer->endElement(); // input
self::$indent && $writer->setIndent(true);

$writer->writeRaw($label);
$writer->fullEndElement(); // label
$writer->fullEndElement(); // li

/*
    Remarque sur l'indentation du code généré :

    XMLWriter n'indente pas correctement les noeuds (en tout cas pas
    comme on veut !) lorsqu'on génère un noeud de type text
    (le writeRaw(label) ci-dessus).

    Sur le fond il a raison : il n'a pas le droit "d'inventer" des
    espaces car cela pourrait changer la sémantique du code.

    Pour obtenir le code html lisible qu'on veut, on désactive donc
    temporairement l'indentation et on la remet en place après :

    if (self::$indent) // l'option indentation est activée
        $writer->setIndent(false);  // désactive temporairement
*/