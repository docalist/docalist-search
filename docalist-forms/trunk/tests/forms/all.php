<?php
use Docalist\Forms\Form;

$repeat = true;

$form = new Form();
$form->label('Un formulaire avec tous les types de champs');

$form->button()
     ->label('button')
     ->repeatable($repeat);

$form->checkbox('checkbox')
     ->label('checkbox')
     ->repeatable($repeat);

$form->checklist('checklist')
     ->label('checklist')
     ->repeatable($repeat)
     ->options(array(
        'un',
        'deux'
     ));

$form->fieldset('un fieldset')
     ->repeatable($repeat);

$form->hidden('hidden')
     ->label('hidden')
     ->repeatable($repeat);

$form->input('input')
     ->label('input')
     ->repeatable($repeat);

$form->password('password')
     ->label('password')
     ->repeatable($repeat);

$form->radio('radio')
     ->label('radio')
     ->repeatable($repeat);
/*
$form->radiolist('radiolist')
     ->label('radio')
     ->repeatable($repeat)
     ->options(array(
        'un',
        'deux'
     ));
*/
$form->reset()
     ->label('reset')
     ->repeatable($repeat);

$form->select('select')
     ->label('select')
     ->repeatable($repeat);

$form->submit()
     ->label('submit')
     ->repeatable($repeat);

$form->table('table')
     ->label('table')
     ->repeatable($repeat);

$form->tag('p', 'tag p')
     ->repeatable($repeat);

$form->textarea('textarea')
     ->label('textarea')
     ->repeatable($repeat);

$form->submit('Go !');

return $form;