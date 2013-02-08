<fieldset>
    <?php if ($this->label) : ?>
    <legend><?php echo $this->label ?></legend>
    <?php endif ?>
    <?php $this->render($theme, 'widget', $args, true) ?>
</fieldset>
