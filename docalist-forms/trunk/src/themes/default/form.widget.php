<?php
$writer->startElement('form');
$this->render($theme, 'attributes');
$this->render($theme, 'widget', $args, true);
$writer->fullEndElement();