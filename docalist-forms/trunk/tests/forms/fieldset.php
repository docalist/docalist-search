<?php
use Docalist\Forms\Form;

$form = new Form();
$form->label('Un formulaire contenant deux fieldsets')->description('Chaque fieldset contient quelques champs.');

$form->input('i1')->label('Hors fieldset');

$fieldset = $form->fieldset('Coordonnées');

$fieldset->select('m')->label('Civilité :')->options(array(
    'Mme',
    'Mle',
    'M' => 'Monsieur'
));
$fieldset->input('surname')->label('Nom : ');
$fieldset->input('firstname')->label('Prénom : ');
$fieldset->textarea('adresse')->label('Votre message : ');

$form->input('i2')->label('Hors fieldset');

$form->submit('Go !');

return $form;
