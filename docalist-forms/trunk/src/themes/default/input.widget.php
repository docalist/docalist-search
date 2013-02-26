<?php
$writer->startElement('input');
$this->name && $writer->writeAttribute('name', $this->controlName());
$this->block('attributes', $args);
$this->data && $writer->writeAttribute('value', $this->data);
$writer->endElement();