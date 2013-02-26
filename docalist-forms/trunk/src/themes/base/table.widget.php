<?php
$writer->startElement('tr');
$this->block('attributes', $args);

foreach($this->fields as $field) {
    $writer->startElement('td');
    $class = $field->attribute('class');
    $writer->writeAttribute('class', $class);

    $field->removeClass('span1 span2 span3 span4 span5 span6 span7 span8 span9 span10 span11 span12');
    //$field->addClass('input-block-level');
    $field->attribute('style','width:100%');

    $field->block('values');
    $writer->fullEndElement(); // </td>
}
$writer->fullEndElement(); // </tr>