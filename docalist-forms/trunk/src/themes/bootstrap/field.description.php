<?php
$writer->startElement('p');
$writer->writeAttribute('class', 'help-block');
(! $this->descriptionAfter) && $writer->writeAttribute('style', 'margin-top: 5px');
$writer->writeRaw($this->description);
$writer->fullEndElement();
