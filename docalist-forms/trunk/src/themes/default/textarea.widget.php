<?php
$writer->startElement('textarea');
$this->name && $writer->writeAttribute('name', $this->controlName());
$this->render($theme, 'attributes', $args);
$this->data && $writer->text($this->data);
$writer->fullEndElement();