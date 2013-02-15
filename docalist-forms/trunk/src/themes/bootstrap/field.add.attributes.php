<?php
// On se contente d'appeller le template par défaut en indiquant les
// classe supplémentaires à générer.
$this->render('default', $template, array('class' => 'btn btn-mini') + $args);