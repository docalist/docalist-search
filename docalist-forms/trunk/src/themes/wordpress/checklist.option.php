<?php
$writer->startElement('label');
self::$indent && $writer->setIndent(false);
$writer->startElement('input');
$writer->writeAttribute('name', $this->controlName());
$writer->writeAttribute('type', 'checkbox');
$writer->writeAttribute('value', is_null($value) ? $label : $value);
$selected && $writer->writeAttribute('checked', 'checked');
$writer->endElement(); // input
self::$indent && $writer->setIndent(true);
$writer->text(' '); // 0160
$writer->writeRaw($label);
$writer->writeRaw('<br />');
$writer->fullEndElement(); // label
