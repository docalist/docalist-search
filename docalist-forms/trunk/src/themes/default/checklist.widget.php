<?php
$writer->startElement('div');
$this->render($theme, 'attributes');
$this->render($theme, 'options');
$writer->fullEndElement();
