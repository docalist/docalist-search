<?php
use Docalist\Forms\Form;

$form = new Form();
$form->label('Saisie des auteurs dans une table');

$form->input('test')->label('test');

$author = $form->table('author')->repeatable(true)->label('Personnes');
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
