<?php
use Docalist\Forms\Form;

$form = new Form();
$form->label('Saisie d\'un auteur');

$form->input('test')->label('test');

$author = $form->fieldset('auteur répétable+prénom répétable')->name('author')->repeatable("fdsfsd");
$author->input('surname')->label('Nom');
$author->input('firstname')->label('Prenom')->repeatable(true);
$author->select('role')->label('Rôle')->options(array(
    'trad.',
    'pref.',
));

$form->submit('Go !');

$form->bind(array(
    'test' => 'test',
    'author' => array(
        array(
            'surname' => 'Ménard',
            'firstname' => array(
                'Daniel',
                'Etienne',
                'Louis',
            ),
        ),
        array(
            'surname' => 'Goron',
            'firstname' => array(
                'Gaëlle',
                'Solange',
            ),
        ),
    )
));

return $form;
