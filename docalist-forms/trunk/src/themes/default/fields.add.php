<?php
$writer->startElement('button');
$writer->writeAttribute('type', 'button');
$writer->writeAttribute('class', 'cloner');
$writer->writeAttribute('data-clone', '^^^^tbody>tr:last-child');
$writer->writeAttribute('title', 'Ajouter ' . lcfirst($this->label));
$writer->text('+ ' . $this->label);
$writer->fullEndElement();

