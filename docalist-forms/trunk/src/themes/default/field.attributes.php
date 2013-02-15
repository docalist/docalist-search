<?php
foreach ($this->attributes as $name => $value) {
    $writer->writeAttribute($name, $value);
}
