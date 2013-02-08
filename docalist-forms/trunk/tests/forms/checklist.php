<?php
use Docalist\Forms\Form;

$form = new Form();
$form->label('Une checklist')->description('Les couleurs que j\'aime bien.');

$form->checklist('colors')->label('Couleurs :')->options(array(
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

$form->select('taints')->label('Couleurs :')->options(array(
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

$form->submit('Go !');

return $form;
