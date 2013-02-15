<?php
$writer->startElement('p');
$writer->writeAttribute('style','margin: 5px 0 0 0;');
$writer->writeRaw($label);
$writer->endElement();

$this->render($theme, 'optgroup', $args, true);
