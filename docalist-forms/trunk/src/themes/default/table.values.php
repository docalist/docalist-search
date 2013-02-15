<?php
$writer->startElement('table');
$this->render($theme, 'attributes', $args);

// Entête du tableau : nom des champs
$writer->startElement('thead');
$writer->startElement('tr');
foreach($this->fields as $field) {
    $writer->startElement('th');
    $writer->writeAttribute('scope', 'col');
    $writer->text($field->label ?: $field->name);
    $writer->fullEndElement(); // </th>
}
$writer->fullEndElement(); // </tr>
$writer->fullEndElement(); // </thead>

// Corps du tableau : liste des valeurs
$writer->startElement('tbody');
$data = $this->data ?: array(null);
foreach($data as $i=>$data) {
    $this->occurence($i);
    $this->bindOccurence($data);
    $this->render($theme, 'widget', $args);
}
$writer->fullEndElement(); // </tbody>

// Pied du tableau : bouton "ajouter une ligne"
if ($this->repeatable) {
    $writer->startElement('tfoot');

    $writer->startElement('tr');
    $writer->startElement('td');
    $writer->writeAttribute('colspan', count($this->fields));
    $this->render($theme, 'add', $args);
    $writer->fullEndElement(); // </th>

    $writer->fullEndElement(); // </tr>

    $writer->fullEndElement(); // </foot>
}

$writer->fullEndElement(); // </table>