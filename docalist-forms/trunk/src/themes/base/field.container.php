<?php
$writer->startElement('div');
$writer->writeAttribute('class', 'dcl-row dcl-' . $this->type());
$this->label && $this->block('label');

$writer->startElement('div');
$writer->writeAttribute('class', 'dcl-wrapper');

$this->description && (! $this->descriptionAfter) && $this->block('description');
$this->block('errors');
$this->block('values');
$this->description && $this->descriptionAfter && $this->block('description');
$writer->fullEndElement();

$writer->fullEndElement();