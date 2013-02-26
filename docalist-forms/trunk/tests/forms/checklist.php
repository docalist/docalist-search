<?php
use Docalist\Forms\Form;

$colors = array(
    'Claires :' => array(
        'beige',
        'j' => 'jaune',
        'orange'
    ),
    'Sombres :' => array(
        'noir',
        'gris',
        'marron'
    ),
);

$form = new Form();
$form->label('Test des checklist');

foreach(array(1=>false, 2=>true) as $i=>$repeat) {
    $form->checklist("empty$i")
         ->label('Vide :')
         ->description('Une checklist vide, aucune option n\'a été fournie.')
         ->repeatable($repeat);

    $form->checklist("clair$i")
         ->label('Couleurs :')
         ->description('Une checklist simple, trois options de base sans attribut "value".')
         ->repeatable($repeat)
         ->options($colors['Sombres :']);

    $form->checklist("sombre$i")
         ->label('Couleurs :')
         ->description('Une checklist simple, un attribut value="j" a été indiqué pour la couleur jaune.')
         ->repeatable($repeat)
         ->options($colors['Claires :']);

    $form->checklist("group$i")
         ->label('Couleurs :')
         ->description('Une checklist hiérarchique contenant des optgroup.')
         ->repeatable($repeat)
         ->options($colors);

    $form->checklist("group$i")
         ->addClass('inline')
         ->label('Couleurs :')
         ->description('INLINEUne checklist hiérarchique contenant à la fois des options simples et des groupes.')
         ->repeatable($repeat)
         ->options(array('transparent', 'blanc') + $colors + array('opaque'));

    if ($i === 1) {
        $form->tag('h3', 'Faisons maintenant la même chose mais en mettant l\'attribut repeatable à true :');
    }
}
$form->submit('Go !');

return $form;
