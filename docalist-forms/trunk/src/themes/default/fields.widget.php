<?php
foreach($this->fields as $field) {
    $field->render($theme, 'container');
}