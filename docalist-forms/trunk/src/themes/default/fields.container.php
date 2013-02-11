<?php
// comme field.container.php, sauf que l'ordre n'est pas le même (description avant)
$writer->startElement('div');
$this->label && $this->render($theme, 'label', $args);
$this->description && $this->render($theme, 'description', $args);
$this->render($theme, 'errors', $args);
$this->render($theme, 'values', $args);
$writer->fullEndElement();