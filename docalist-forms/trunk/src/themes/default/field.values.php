<?php
if (! $this->repeatable) {
    $this->render($theme, 'widget');
}

else
{
    if ($this->data) {
        foreach($this->data as $i=>$data) {
            $this->occurence($i);
            $this->bindOccurence($data);
            $this->render($theme, 'widget');
        }
    }
    $writer->writeElement('button', 'Ajouter ' . $this->label);
}
