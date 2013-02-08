<?php
use Docalist\Forms\Form;

$form = new Form();
$form->label('Formulaire de test pour les select');
$t = array('Multiple=false' => false, 'Multiple=true' => true);

foreach ($t as $title=>$multiple) {

    $form->tag('h4', $title);

    $form->select('m')->label('que des options, pas de value :')->multiple($multiple)->options(array(
        'Mme',
        'Mle',
        'Monsieur'
    ));

    $form->select('m')->label('que des options, monsieur a une value :')->multiple($multiple)->options(array(
        'Mme',
        'Mle',
        'M' => 'Monsieur'
    ));

    $form->select('n')->label('que des optgroup, value pour jaune')->multiple($multiple)->options(array(
        'sombres' => array(
            'noir',
            'gris',
            'marron'
        ),
        'claires' => array(
            'blanc',
            'J'=>'jaune',
            'orange'
        ),
    ));

    $form->select('n')->label('trois options puis un optgroup puis deux options')->multiple($multiple)->options(array(
        'noir',
        'gris',
        'marron',
        'claires' => array(
            'blanc',
            'j'=>'jaune',
            'orange'
        ),
        'bleu',
        'V' => 'vert',
    ));
}
$form->submit('Go !');

return $form;
