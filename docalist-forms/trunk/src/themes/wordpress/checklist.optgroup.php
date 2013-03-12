<?php
$writer->startElement('p');
$writer->text('label du optgroup');
$writer->writeRaw($label);
$writer->endElement();

$this->parentBlock($args);
