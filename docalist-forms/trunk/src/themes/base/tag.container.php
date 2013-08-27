<?php
$this->description() && (! $this->descriptionAfter) && $this->block('description');
$this->block('errors');
$this->block('values');
$this->description() && $this->descriptionAfter && $this->block('description');
