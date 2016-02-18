<?php
/**
 * This file is part of the 'Docalist Search' plugin.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search\Views;

use Docalist\Search\SettingsPage;
use Docalist\Forms\Form;
use stdClass;

/**
 * Affiche un dump du contenu des documents dans l'index ElasticSearch.
 *
 * @var SettingsPage    $this
 * @var string          $query      Requête exécutée.
 * @var stdClass        $response   Réponse ElasticSearch.
 */
?>
<div class="wrap">
    <h1><?= __('Debug', 'docalist-search') ?></h1>

    <?php
        $form = new Form();
        $form->input('query', ['class' => 'large-text'])->setLabel('Query');
        $form->submit(__('Rechercher', 'docalist-search'))->addClass('button button-primary');

        $form->bind(['query' => $query])->display();

        //echo '<pre>', var_export($response, true), '</pre>';
        if (empty($response->hits->hits)) {
            echo '<p>Aucune réponse</p>';
        } else {
            echo '<table border="0" class="widefat">';
            echo '<tr><th>Champ</th><th>Contenu</th><th>Termes indexés</th></tr>';
            foreach($response->hits->hits as $hit) {
                $rows = [];
                $fields = $hit->fields;

                // Meta champs
                foreach(['_index', '_type', '_id', '_uid'] as $field) {
                    $rows[$field] = [$hit->$field, []];
                }

                // Champs présents dans _source
                $source = $hit->_source;
                foreach($source as $field => $value) {
                    $rows[$field] = [$value, isset($fields->$field) ? $fields->$field : []];
                    unset($fields->$field);
                }

                // Champs présents dans fields mais pas dans _source
                foreach($fields as $field => $value) {
                    $rows[$field] = ['', $value];
                }

                ksort($rows);
                printf('<tr class="alternate"><td colspan="3"><hr /><h2>%s/%s</h2></td></tr>', $hit->_type, $hit->_id);

                $alt = false;
                foreach($rows as $field => $row) {
                    list($value, $terms) = $row;
                    $value = $value === '' ? '' : json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    foreach($terms as & $term) {
                        $term = '<code>' . json_encode($term, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</code>';
                    }
                    unset($term);
                    $terms = implode(' ', $terms);

                    printf(
                        '<tr class="%s"><td class="row-title">%s</td><td>%s</td><td>%s</td></tr>',
                        $alt ? 'alternate' : '',
                        $field,
                        htmlspecialchars($value),
                        $terms
                    );

                    $alt = !$alt;
                }
            }
            echo "</table>";
        }
    ?>
</div>