<?php
if ($this->name) {
    $this->htmlAttribute('name', $this->controlName());
}

foreach ($this->attributes as $name => $value) {
    $this->htmlAttribute($name, $value);
}
