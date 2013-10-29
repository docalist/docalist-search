<?php
// Les arguments passés en paramêtre ($args) sont écrits comme attributs du tag
// <label> généré. Si l'argument "for" est à false, aucun attribut for n'est
// généré.
$writer->startElement('label');
if (isset($args['for'])) {
    if (! $args['for']) unset($args['for']); // null, false, '', etc.
} else {
    $args['for'] = $this->generateId();
}

foreach ($args as $name => $value) {
    $writer->writeAttribute($name, $value);
}
// Bulle d'aide
// $writer->writeAttribute('title', $this->description());
$writer->writeRaw($this->label());
$writer->fullEndElement();
