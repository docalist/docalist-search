<?php
use Docalist\Forms\Form;

// reproduction du formulaire affiché en haut de la page
// http://reactiveraven.github.com/jqBootstrapValidation/

$form = new Form();
$form->label('Into this')
     ->description('Reproduction du formulaire affiché en haut de <a href="http://reactiveraven.github.com/jqBootstrapValidation/">cette page</a>', false);

$form->input('email')
     ->label('Email address')
     ->description('Email address we can contact you on');

$form->input('emailAgain')
     ->label('Email again')
     ->description('And again, to check for speeling miskates');

$form->checklist('terms-and-conditions')
     ->label('Legal')
     ->options(array(
        'on' => 'I agree to the <a href="#">terms and conditions</a>'
     ));

$form->checklist('qualityControl')
     ->label('Quality Control')
     ->options(array(
        'fast' => 'Fast',
        'cheap' => 'Cheap',
        'good' => 'Good',
     ));

$form->submit('Test Validation')
     ->addClass('btn-primary')
     ->description('(go ahead, nothing is sent anywhere)');

return $form;