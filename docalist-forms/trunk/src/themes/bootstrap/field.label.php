<?php
$writer->startElement('label');
$writer->writeAttribute('class', 'control-label');
$writer->writeAttribute('for', $this->generateId());
$writer->writeRaw($this->label);
$writer->fullEndElement();