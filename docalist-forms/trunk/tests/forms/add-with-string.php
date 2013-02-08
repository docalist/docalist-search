<?php
use Docalist\Forms\Form;

$form = new Form();
$form->label('Utilisation de add() avec une chaine')
     ->description('Dans ce formulaire, les champs sont construits via des appels de la forme add(\'input\').');

$form->add('select', 'sex')->label('Civilité')->options(array('Mme', 'Mle', 'M.'));
$form->add('input', 'surname')->label('Nom');
$form->add('input', 'firstname')->label('Prénom');
$form->add('textarea', 'profile')->label('Votre profil');

$form->add('submit', 'Go !');

return $form;
