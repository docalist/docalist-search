<select<?php $this->render($theme, 'attributes') ?>><?php
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
?></select>