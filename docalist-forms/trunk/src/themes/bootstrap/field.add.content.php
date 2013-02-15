<?php
$writer->startElement('i');
$writer->writeAttribute('class', 'icon-plus-sign');
$writer->fullEndElement(); // </i>
$writer->text(' ' . $this->label);
