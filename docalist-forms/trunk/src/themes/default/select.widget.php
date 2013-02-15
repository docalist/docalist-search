<?php
$writer->startElement('select');
$this->name && $writer->writeAttribute('name', $this->controlName());
$this->render($theme, 'attributes', $args);

if (! $this->multiple()) // && (! $this->required())
{

    $option = $this->firstOption();
    $this->render($theme, 'option', $args + array(
        'value' => $option['value'],
        'label' => $option['label'],
        'selected' => false,
    ));
}

$this->render($theme, 'options', $args);

$writer->fullEndElement();