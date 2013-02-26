<?php
use Docalist\Forms\Checklist;

$ctl = new Checklist('simple');

$ctl->label('Libellé de la checklist')
    ->options(array('value1'=>'option 1', 'value2' => 'option 2'))
    ->description('Description de la checklist.');

return $ctl;
