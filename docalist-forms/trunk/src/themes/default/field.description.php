<?php
$writer->startElement('p');
$writer->writeAttribute('class', $this->descriptionAfter ? 'description' : 'description after');
$writer->writeRaw($this->description);
$writer->fullEndElement();
