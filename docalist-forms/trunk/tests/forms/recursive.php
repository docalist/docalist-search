<?php
use Docalist\Forms\Form;

$form = new Form();
$form->label('Saisie des auteurs dans une table');

$form->input('test')->label('test');

$f1 = $form->fieldset('fieldset A');
$f2 = $f1->fieldset('fieldset A1');
$f3 = $f2->fieldset('fieldset A11');
$f4 = $f3->fieldset('fieldset A111')->input('inputA111')->label('input A111');

$f2 = $f1->fieldset('fieldset A2');
$f3 = $f2->fieldset('fieldset A21');
$f4 = $f3->fieldset('fieldset A211')->checkbox('inputA211')->label('input A211');

$f3 = $f2->fieldset('fieldset A22');
$f4 = $f3->fieldset('fieldset A221')->radio('inputA221')->label('input A221');

$f1 = $form->fieldset('fieldset B');
$f2 = $f1->fieldset('fieldset B.1');
$f3 = $f2->fieldset('fieldset B.2');
$f4 = $f3->fieldset('fieldset B.3')->button('inputB.3')->label('input B.3');

$form->submit('Go !');

return $form;
