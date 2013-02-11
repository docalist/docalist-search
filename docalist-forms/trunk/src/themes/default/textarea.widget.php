<?php
$writer->startElement('textarea');
$this->render($theme, 'attributes');
$this->data && $writer->text($this->data);
$writer->fullEndElement();