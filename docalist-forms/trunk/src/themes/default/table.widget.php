<?php
$writer->startElement('tr');
foreach($this->fields as $field) {
    $writer->startElement('td');
    $field->render($theme, 'values');
    $writer->fullEndElement(); // </td>
}
$writer->fullEndElement(); // </tr>