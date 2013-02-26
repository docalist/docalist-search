<?php
$writer->startElement('button');
$this->name && $writer->writeAttribute('name', $this->controlName());
$this->block('attributes', $args);
$this->label && $writer->text($this->label);
$writer->fullEndElement();