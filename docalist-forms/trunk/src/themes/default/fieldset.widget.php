<?php
$writer->startElement('fieldset');
$this->label && $writer->writeElement('legend', $this->label);
$this->render($theme, 'widget', $args, true);
$writer->fullEndElement();