<?php
$writer->startElement('p');
if ($this->descriptionAfter) {
    $writer->writeAttribute('class', 'description after');
} else {
    $writer->writeAttribute('class', 'description');
}
$writer->text($this->description);
// html ?
$writer->fullEndElement();
