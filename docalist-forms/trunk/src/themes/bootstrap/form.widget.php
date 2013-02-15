<?php
// TODO : à enlever
if (! $this->hasClass('form-search form-inline form-horizontal form-vertical')) {
    $this->addClass('form-horizontal');
}

$writer->startElement('form');
$this->render($theme, 'attributes', $args);
$this->render($theme, 'widget', $args, true);
$writer->fullEndElement();