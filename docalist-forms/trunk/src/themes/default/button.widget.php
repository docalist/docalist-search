<?php
$writer->startElement('button');
$this->render($theme, 'attributes');
$writer->text($this->label);
$writer->fullEndElement();