<?php
$writer->startElement('ul');
$this->render($theme, 'attributes', $args);
$this->render($theme, 'options', $args);
$writer->fullEndElement();
