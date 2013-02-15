<?php
// insère un espace (non significtif) avant le bouton
// pour éviter qu'il ne "colle" au contrôle qui précède
$writer->text(' ');
$writer->startElement('button');
$writer->writeAttribute('type', 'button');
$writer->writeAttribute('class', 'cloner');
$writer->writeAttribute('title', 'Ajouter ' . lcfirst($this->label));
$writer->text('+');
$writer->fullEndElement();

