<?php
$writer->startElement('select');
$this->render($theme, 'attributes');

if (! $this->multiple()) // && (! $this->required())
{

    $option = $this->firstOption();
    $this->render($theme, 'option', array(
        'value' => $option['value'],
        'label' => $option['label'],
        'selected' => false,
    ));
}

$this->render($theme, 'options');

$writer->fullEndElement();