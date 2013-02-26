<?php
if (!$this->repeatable) {
    $this->block('widget');
} else {
    $data = $this->data ? : array(null);
    foreach ($data as $i => $data) {
        $this->occurence($i);
        $this->bindOccurence($data);
        $this->block('widget');
    }
    $this->block('add');
}
