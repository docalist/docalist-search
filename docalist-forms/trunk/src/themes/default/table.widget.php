<tr>
    <?php foreach($this->fields as $field) : ?>
        <td scope="col"><?php echo $field->render($theme, 'values') ?></td>
    <?php endforeach ?>
</tr>
