<?php
use Docalist\Forms\Form;

$form = new Form();
$form->label('Ecrivez-moi !')->description('Utilisez le formulaire ci-dessous pour nous adresser un message.');

$form->select('civilite')->label('Civilité :')->options(array(
    'Mme',
    'Mle',
    'M.' => 'Monsieur'
));
$form->input('surname')->label('Nom : ');
$form->input('firstname')->label('Prénom : ');
$form->textarea('message')->label('Votre message : ');

$form->submit('Go !');

return $form;
