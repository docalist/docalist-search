<?php
$writer->startElement('select');
$this->name && $writer->writeAttribute('name', $this->controlName());
$this->block('attributes', $args);

if (! $this->multiple()) // && (! $this->required())
{
    $option = $this->firstOption();
    $option && $this->block('option', array(
        'value' => $option['value'],
        'label' => $option['label'],
        'selected' => false,
    ));
}

$notfound = $this->block('options');

$writer->fullEndElement();

if (! empty($notfound)) {
    $msg = '<span style="color: red;" title="%s%s"> !!! </span>';
    $msg = sprintf($msg,
        'Le champ contient des valeurs qui ne figurent pas dans la liste : ',
        implode(', ', $notfound)
    );
    $writer->writeRaw($msg);
}