<?php
// On se contente d'appeller le template par défaut en indiquant le
// sélecteur à générer pour l'attribut data-clone.
$this->parentBlock($args + array('data-clone' => '<tbody>tr:last-child'));
