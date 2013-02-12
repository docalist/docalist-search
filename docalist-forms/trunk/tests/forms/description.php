<?php
use Docalist\Forms\Form;

$repeat = true;
$desc = 'Description du champ';

$form = new Form();
$form->label('Un formulaire avec tous les types de champs');

$pos = array(
    'Description affichée à sa position par défaut' => null,
    'Description affichée en haut (avant le champ)' => false,
    'Description affichée en bas (après le champ)' => true,
);

foreach($pos as $title => $pos) {
    $form->tag('h3', $title);

    $form->button()
         ->label('button')
         ->description($desc, $pos)
         ->repeatable($repeat);

    $form->checkbox('checkbox')
         ->label('checkbox')
         ->description($desc, $pos)
         ->repeatable($repeat);

    $form->checklist('checklist')
         ->label('checklist')
         ->description($desc, $pos)
         ->repeatable($repeat)
         ->options(array(
            'un',
            'deux'
         ));

    $form->fieldset('un fieldset')
         ->description($desc, $pos)
         ->repeatable($repeat);

    $form->hidden('hidden')
         ->label('hidden')
         ->description($desc, $pos)
         ->repeatable($repeat);

    $form->input('input')
         ->label('input')
         ->description($desc, $pos)
         ->repeatable($repeat);

    $form->password('password')
         ->label('password')
         ->description($desc, $pos)
         ->repeatable($repeat);

    $form->radio('radio')
         ->label('radio')
         ->description($desc, $pos)
         ->repeatable($repeat);
    /*
    $form->radiolist('radiolist')
         ->label('radio')
         ->description($desc, $pos)
         ->repeatable($repeat)
         ->options(array(
            'un',
            'deux'
         ));
    */
    $form->reset()
         ->label('reset')
         ->description($desc, $pos)
         ->repeatable($repeat);

    $form->select('select')
         ->label('select')
         ->description($desc, $pos)
         ->repeatable($repeat);

    $form->submit()
         ->label('submit')
         ->description($desc, $pos)
         ->repeatable($repeat);

    $form->table('table')
         ->label('table')
         ->description($desc, $pos)
         ->repeatable($repeat);

    $form->tag('p')
         ->label('tag p')
         ->description($desc, $pos)
         ->repeatable($repeat);

    $form->textarea('textarea')
         ->label('textarea')
         ->description($desc, $pos)
         ->repeatable($repeat);

    $form->submit('Go !');
}
return $form;