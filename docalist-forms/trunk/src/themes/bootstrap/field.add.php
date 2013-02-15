<?php
// insère un espace (non significatif) avant le bouton
// pour éviter qu'il ne "colle" au contrôle qui précède
$writer->text(' ');
$writer->startElement('button');
$writer->writeAttribute('type', 'button');
$writer->writeAttribute('class', 'btn btn-mini cloner');

// $writer->writeAttribute('data-selector', '<');

$level = $this->repeatLevel();
$level > 1 && $writer->writeAttribute('data-level', $level);

// $writer->writeAttribute('data-selector', '<');
$writer->writeAttribute('title', 'Ajouter ' . lcfirst($this->label));

$writer->startElement('i');
$writer->writeAttribute('class', 'icon-plus-sign');
$writer->fullEndElement(); // </i>

$writer->fullEndElement(); // </button>