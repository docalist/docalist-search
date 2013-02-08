<div><?php
    // comme field, sauf que l'ordre n'est pas le même
    $this->label && $this->render($theme, 'label', $args);
    $this->description && $this->render($theme, 'description', $args);
    $this->render($theme, 'errors', $args);
    $this->render($theme, 'values', $args); ?>
</div>