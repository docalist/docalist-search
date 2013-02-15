<?php
$writer->startElement('label');
$writer->writeAttribute('class', 'checkbox' . ($this->hasClass('inline') ? ' inline' : ''));

self::$indent && $writer->setIndent(false);
$writer->startElement('input');
$writer->writeAttribute('name', $this->controlName());
$writer->writeAttribute('type', 'checkbox');
$writer->writeAttribute('value', is_null($value) ? $label : $value);
$selected && $writer->writeAttribute('checked', 'checked');
$writer->endElement(); // input
self::$indent && $writer->setIndent(true);

$writer->writeRaw($label);
$writer->fullEndElement(); // label
