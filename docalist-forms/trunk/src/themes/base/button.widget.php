<?php
$writer->startElement('button');
$this->name() && $writer->writeAttribute('name', $this->controlName());
$this->block('attributes', $args);
if ($label = $this->label()) $writer->text($label);
$writer->fullEndElement();