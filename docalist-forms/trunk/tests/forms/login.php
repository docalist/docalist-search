<?php
use Docalist\Forms\Form;

// reproduction du formulaire affiché en haut de la page
// http://reactiveraven.github.com/jqBootstrapValidation/

$form = new Form();
$form->label('Connexion au site')
     ->description('Un formulaire de connexion', false);

$form->input('login')
     ->label('Login')
     ->attribute('placeholder', 'Indiquez votre nom d\'utilisateur');

$form->password('password')
     ->label('Mot de passe')
     ->attribute('placeholder', 'Votre mot de passe')
     ->description('<a href="#">J\'ai oublié mon mot de passe...</a>');

$form->checkbox('rememberme')
     ->label('Se souvenir de moi');

$form->submit('Connexion')
     ->addClass('btn-primary');

$form->submit('Annuler')
     ->addClass('');

return $form;