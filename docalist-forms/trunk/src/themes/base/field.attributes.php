<?php
// Tous les arguments passés en paramètre dans $args sont écrit sous
// forme d'attributs du noeud en cours.
// Si le champ a un attribut avec le même nom, les valeurs sont concaténées
// avec un espace (comme pour class).

foreach ($this->attributes() as $name => $value) {
    if (isset($args[$name])) {
        $value .= ' ' . $args[$name];
        unset($args[$name]);
    }
    $writer->writeAttribute($name, $value);
}

foreach($args as $name => $value) {
    $writer->writeAttribute($name, $value);
}