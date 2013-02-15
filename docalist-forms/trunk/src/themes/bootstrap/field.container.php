<?php
$writer->startElement('div');
$writer->writeAttribute('class', 'control-group field-' . $this->type());
    $this->label && $this->render($theme, 'label', $args);
    $writer->startElement('div');
    $writer->writeAttribute('class', 'controls');
        $this->description && (! $this->descriptionAfter) && $this->render($theme, 'description', $args);
        $this->render($theme, 'errors', $args);
        $this->render($theme, 'values', $args);
        $this->description && $this->descriptionAfter && $this->render($theme, 'description', $args);
    $writer->fullEndElement();
$writer->fullEndElement();