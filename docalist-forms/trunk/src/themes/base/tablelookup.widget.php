<?php
// Garantit que le contrôle a un ID
$this->generateId();

$args['data-table'] = $this->table();
$this->valueField() !== 'code' && $args['data-valueField'] = $this->valueField();
$this->labelField() !== 'label' && $args['data-labelField'] = $this->labelField();

$this->parentBlock($args);

$writer->startElement('script');
$writer->writeAttribute('type', 'text/javascript'); // pas nécessaire en html5

$id = $this->attribute('id');
$writer->writeRaw("jQuery('#$id').tableLookup();");

$writer->fullEndElement();