<?php
$writer->startElement('input');
$this->name && $writer->writeAttribute('name', $this->controlName());
$this->render($theme, 'attributes', $args);
$this->data && $writer->writeAttribute('value', $this->data);
$writer->endElement();