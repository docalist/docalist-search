<?php
$writer->startElement('ul');
$this->block('attributes', $args);
$this->block('options');
$writer->fullEndElement();
