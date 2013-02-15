<?php
if (! $this->repeatable) {
    $this->render($theme, 'widget', $args);
}

else
{
    $data = $this->data ?: array(null);
    foreach($data as $i=>$data) {
        $this->occurence($i);
        $this->bindOccurence($data);
        $this->render($theme, 'widget', $args);
    }
    $this->render($theme, 'add', $args);
}