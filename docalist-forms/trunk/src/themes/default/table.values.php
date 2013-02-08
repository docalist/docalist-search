<table>
    <thead>
        <tr>
            <?php foreach($this->fields as $field) : ?>
                <th scope="col"><?php echo $field->label ?></th>
            <?php endforeach ?>
        </tr>
    </thead>
    <tbody>
        <?php
            if ($this->data) {
                foreach($this->data as $i=>$data) {
                    $this->occurence($i);
                    $this->bindOccurence($data);
                    $this->render($theme, 'widget');
                }
            }
        ?>
    </tbody>
    <?php if ($this->repeatable) : ?>
        <tfoot>
            <tr>
                <td colspan="<?php echo count($this->fields) ?>">
                    <button>
                        <?php echo 'Ajouter ', $this->label ?>
                    </button>
                </td>
            </tr>
        </tfoot>
    <?php endif ?>
</table>
