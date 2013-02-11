<?php
$writer->startElement('p');
$writer->writeAttribute('class', 'description');
$writer->text($this->description); // html ?
$writer->fullEndElement();