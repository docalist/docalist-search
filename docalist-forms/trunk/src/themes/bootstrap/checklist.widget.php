<?php
// on est obligé d'avoir un block div englobant si la checklist est répétable
if ($this->repeatable) $writer->startElement('div');
$this->render($theme, 'options', $args);
if ($this->repeatable) $writer->fullEndElement();
