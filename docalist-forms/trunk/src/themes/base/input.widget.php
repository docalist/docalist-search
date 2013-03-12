<?php
$writer->startElement('input');

if ($this->name) {
    $args['name'] = $this->controlName();
}

if ($this->data && (!isset($this->attributes['value']))) {
    $args['value'] = $this->data;
}
$this->block('attributes', $args);

$writer->endElement();