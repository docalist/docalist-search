<?php
use Docalist\Forms\Checklist;

$ctl = new Checklist('hier');

$ctl->label('Libellé de la checklist')
    ->options(array(
        'Libellé du groupe 1' => array('value1'=>'option 1', 'value2' => 'option 2'),
        'Libellé du groupe 2' => array('value3'=>'option 3', 'value4' => 'option 4'),
    ))
    ->description('Description de la checklist.');

return $ctl;
