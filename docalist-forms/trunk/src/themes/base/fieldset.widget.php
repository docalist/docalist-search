<?php
$writer->startElement('fieldset');
$this->block('attributes', $args);
$this->parentBlock($args);
$writer->fullEndElement();