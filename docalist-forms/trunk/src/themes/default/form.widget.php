<?php
$writer->startElement('form');
$this->name && $writer->writeAttribute('name', $this->controlName());
$this->render($theme, 'attributes', $args);
$this->render($theme, 'widget', $args, true);
$writer->fullEndElement();