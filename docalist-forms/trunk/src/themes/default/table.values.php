<?php
// Tous les arguments passés en paramètre ($arg) sont considérés comme
// des attributs qui sont ajoutés au tag <table>.

$writer->startElement('table');
foreach($args as $name => $value) {
    $writer->writeAttribute($name, $value);
}

// THEAD - nom des champs
$hasDescription = false;
$writer->startElement('thead');
$writer->startElement('tr');
foreach($this->fields as $field) {
    $writer->startElement('th');
    $writer->writeAttribute('scope', 'col');
    $writer->writeRaw($field->label ?: $field->name);
    $hasDescription = $hasDescription || $field->description;
    $writer->fullEndElement(); // </th>
}
$writer->fullEndElement(); // </tr>
$writer->fullEndElement(); // </thead>

// TBODY - liste des valeurs
$writer->startElement('tbody');
$data = $this->data ?: array(null);
foreach($data as $i=>$data) {
    $this->occurence($i);
    $this->bindOccurence($data);
    $this->block('widget');
}
$writer->fullEndElement(); // </tbody>

// TFOOT - bouton "ajouter une ligne"
if ($this->repeatable || $hasDescription) {
    $writer->startElement('tfoot');
    if ($hasDescription) {
        $writer->startElement('tr');
        foreach($this->fields as $field) {
            $writer->startElement('td');
            $writer->writeAttribute('class', 'description');
            $writer->writeRaw($field->description);
            $writer->fullEndElement(); // </td>
        }
        $writer->fullEndElement(); // </tr>
    }
/*
    $writer->startElement('tr');
    $writer->startElement('td');
    $writer->writeAttribute('colspan', count($this->fields));
    $this->block('add');
    $writer->fullEndElement(); // </td>
    $writer->fullEndElement(); // </tr>
*/
    $writer->fullEndElement(); // </foot>
}

$writer->fullEndElement(); // </table>
$this->block('add');
