<?php
$writer->startElement('div');
$writer->writeAttribute('class', 'field ' . $this->type());
$this->label && $this->render($theme, 'label', $args);
$this->description && (! $this->descriptionAfter) && $this->render($theme, 'description', $args);
$this->render($theme, 'errors', $args);
$this->render($theme, 'values', $args);
$this->description && $this->descriptionAfter && $this->render($theme, 'description', $args);
$writer->fullEndElement();