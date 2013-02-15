<?php
$writer->startElement('button');
$this->name && $writer->writeAttribute('name', $this->controlName());
$this->render($theme, 'attributes', $args);
$writer->text($this->label);
$writer->fullEndElement();