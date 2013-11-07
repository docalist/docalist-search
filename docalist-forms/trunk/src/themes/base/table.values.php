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
    $writer->writeAttribute('class', $field->attribute('class'));
/*
    // Bulle d'aide
    if ($description = $field->description()) {
        $writer->writeAttribute('title', $description);
    }
*/
    $writer->writeRaw($field->label() ?: $field->name());
    $hasDescription = $hasDescription || $field->description();
    $writer->fullEndElement(); // </th>
}
$writer->fullEndElement(); // </tr>
$writer->fullEndElement(); // </thead>

// TBODY - liste des valeurs
$writer->startElement('tbody');
if (!$this->repeatable()) {
    $this->block('widget');
} else {
    $data = $this->data ?: array(null);
    foreach($data as $i=>$data) {
        $this->occurence($i);
        $this->bindOccurence($data);
        $this->block('widget');
    }
}
$writer->fullEndElement(); // </tbody>

// TFOOT - description
if ($this->repeatable() || $hasDescription) {
    $writer->startElement('tfoot');
    if ($hasDescription) {
        $writer->startElement('tr');
        foreach($this->fields as $field) {
            $writer->startElement('td');
            $field->block('description');
            $writer->fullEndElement(); // </td>
        }
        $writer->fullEndElement(); // </tr>
    }
    $writer->fullEndElement(); // </foot>
}
$writer->fullEndElement(); // </table>
if ($this->repeatable()) {
    $this->block('add');
}
