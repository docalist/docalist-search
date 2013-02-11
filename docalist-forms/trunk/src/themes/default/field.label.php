<?php
$writer->startElement('label');
$writer->writeAttribute('for', $this->generateId());
$writer->text($this->label);
$writer->fullEndElement();