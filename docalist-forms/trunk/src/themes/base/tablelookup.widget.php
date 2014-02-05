<?php
// Garantit que le contrôle a un ID
$this->generateId();

$args['data-table'] = $this->table();
$this->valueField() !== 'code' && $args['data-valueField'] = $this->valueField();
$this->labelField() !== 'label' && $args['data-labelField'] = $this->labelField();

$this->parentBlock($args);

$writer->startElement('script');
$writer->writeAttribute('type', 'text/javascript'); // pas nécessaire en html5
$writer->writeAttribute('class', 'do-not-clone'); // indique à deocalist-forms.js qu'il ne faut pas cloner cet élément

$id = $this->attribute('id');
$writer->writeRaw("jQuery('#$id').tableLookup();");

$writer->fullEndElement();