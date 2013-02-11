<?php
$this->name && $writer->writeAttribute('name', $this->controlName());

foreach ($this->attributes as $name => $value) {
    $writer->writeAttribute($name, $value);
}
