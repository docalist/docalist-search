<?php
$writer->startElement('button');
$writer->writeAttribute('type', 'button');
$writer->writeAttribute('class', 'btn btn-mini cloner');
$writer->writeAttribute('data-clone', '^^^^tbody>tr:last-child');
$level = $this->repeatLevel();
$level > 1 && $writer->writeAttribute('data-level', $level);

$writer->writeAttribute('title', 'Ajouter ' . lcfirst($this->label));

$writer->startElement('i');
$writer->writeAttribute('class', 'icon-plus-sign');
$writer->fullEndElement(); // </i>
$writer->text(' ' . $this->label);

$writer->fullEndElement(); // </button>