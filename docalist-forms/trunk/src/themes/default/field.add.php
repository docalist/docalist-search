<?php
// Insère un espace (non significatif) avant le bouton
// pour éviter qu'il ne "colle" au contrôle qui précède
$writer->text(' ');
$writer->startElement('button');
$writer->writeAttribute('type', 'button');

// Génère les attributs du bouton (class="cloner", data-clone, data-level)
$this->block('add.attributes');

// Génère le contenu du champ. On passe par un template pour permettre aux
// thèmes descendants de surcharger sans avoir à réécrire tout le bouton
$this->block('add.content');

$writer->fullEndElement();
