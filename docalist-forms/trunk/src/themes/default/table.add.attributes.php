<?php
// On se contente d'appeller le template par défaut en indiquant le
// sélecteur à générer pour l'attribut data-clone.
$this->render($theme, $template, array('selector' => '^^^^tbody>tr:last-child') + $args, true);
