<?php
$writer->startElement('form');
$this->block('attributes', $args);
$this->parentBlock($args);
$writer->fullEndElement();
