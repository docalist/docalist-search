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

$org = $form->table('organisation')->repeatable(true)->label('Organismes');
$org->input('name')->label('Nom');
$org->input('city')->label('Ville');
$org->input('country')->label('Pays');
$org->select('role')->label('Rôle')->options(array(
    'com.',
    'financ.',
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
    ),
    'organisation' => array(
        array(
            'name' => 'docalist',
            'city' => 'Saint-Gilles',
            'country' => 'fra',
        ),
    ),
));

return $form;
