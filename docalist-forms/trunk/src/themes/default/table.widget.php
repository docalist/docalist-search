<?php
$writer->startElement('tr');
foreach($this->fields as $field) {
    $writer->startElement('td');
    $field->render($theme, 'values', $args);
    $writer->fullEndElement(); // </td>
}
$writer->fullEndElement(); // </tr>