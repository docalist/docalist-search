<?php
$this->label() && $this->block('label');
$this->description() && (! $this->descriptionAfter) && $this->block('description');
$this->block('errors');

foreach($this->fields as $field) {
    $field->block('container');
}

$this->description() && $this->descriptionAfter && $this->block('description');
