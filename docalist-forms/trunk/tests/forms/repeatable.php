<?php
use Docalist\Forms\Form;

$form = new Form();
$form->label('Champs répétables');

// -----------------------------------------------------------------------------

$form->table('un')->label('Répétable niveau 1')->repeatable(true)
     ->table('deux')->label('Répétable niveau 2')->repeatable(true)
     ->table('trois')->label('Répétable niveau 3')->repeatable(true)
     ->table('quatre')->label('Répétable niveau 4')->repeatable(true)
     ->table('cinq')->label('Répétable niveau 5')->repeatable(true)
     ->input('data')->repeatable(true);

// -----------------------------------------------------------------------------
$form->submit('Go !');

return $form;
