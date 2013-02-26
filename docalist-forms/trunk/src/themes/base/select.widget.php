<?php
$writer->startElement('select');
$this->name && $writer->writeAttribute('name', $this->controlName());
$this->block('attributes', $args);

if (! $this->multiple()) // && (! $this->required())
{
    $option = $this->firstOption();
    $this->block('option', array(
        'value' => $option['value'],
        'label' => $option['label'],
        'selected' => false,
    ));
}

$this->block('options');

$writer->fullEndElement();