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
    echo '<button>ajouter ', $this->label, '</button>';
}
