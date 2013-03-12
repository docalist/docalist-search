<?php
// on est obligé d'avoir un block div englobant si la checklist est répétable
if ($this->repeatable) $writer->startElement('div');
$this->block('attributes'); // et si pas de div ?
$this->block('options');
if ($this->repeatable) $writer->fullEndElement();
