<?php
use Docalist\Forms\Form;

$form = new Form();
$form->label('Un formulaire tout simple')->description('Deux inputs text et un bouton submit.');
$form->input('surname')->label('Nom : ');
/*
$form->input('firstname')->label('Prénom : ');
$form->textarea('message')->label('Votre message : ');
$form->select('m')->label('Civilité :')->options(array(
    'Mme',
    'Mle',
    'M' => 'Monsieur'
));

$form->select('n')->label('Couleurs :')->multiple(true)->options(array(
    'sombres' => array(
        'noir',
        'gris',
        'marron'
    ),
    'claires' => array(
        'blanc',
        'j'=>'jaune',
        'orange'
    ),
));
*/
$form->submit('Go !');

return $form;
