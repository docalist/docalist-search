<?php
$this->label() && $this->block('label');
$this->description() && (! $this->descriptionAfter) && $this->block('description');
$this->block('errors');

$writer->startElement('table');
$writer->writeAttribute('class', 'form-table');
//$writer->writeAttribute('border', '1');
$hidden = array();
foreach($this->fields as $field) {
    if ($field->type() === 'hidden') {
        $hidden[] = $field;
        continue;
    }
    $writer->startElement('tr');

        $writer->startElement('th');
        $writer->writeAttribute('scope', 'row');
        $writer->writeAttribute('valign', 'top');
            $field->label() && $field->block('label');
        $writer->fullEndElement(); // </th>

        $writer->startElement('td');
        $writer->writeAttribute('valign', 'top');
//            $field->description() && (! $field->descriptionAfter) && $field->block('description');
            $field->block('errors');
            $field->block('values');
//            $field->description() && $field->descriptionAfter && $field->block('description');
        $writer->fullEndElement(); // </td>

    $writer->fullEndElement(); // </tr>
}
$writer->fullEndElement(); // </table>
foreach($hidden as $field) {
    $field->block('values');
}
$this->description() && $this->descriptionAfter && $this->block('description');
