<?php
$writer->startElement('textarea');
$this->name && $writer->writeAttribute('name', $this->controlName());
$this->block('attributes', $args);
$this->data && $writer->text($this->data);
$writer->fullEndElement();