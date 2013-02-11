<?php
$writer->startElement('input');
$this->render($theme, 'attributes');
$this->data && $writer->writeAttribute('value', $this->data);
$writer->endElement();