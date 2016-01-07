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
use Docalist\Search\IndexerSettings;
use Docalist\Forms\Form;

/**
 * Paramètres de l'indexeur.
 *
 * @var SettingsPage    $this
 * @var IndexerSettings $settings   Les paramètres de l'indexeur.
 * @var string          $error      Erreur éventuelle à afficher.
 * @var string[]        $types      Liste des types disponibles.
 */
?>
<div class="wrap">
    <h1><?= __("Paramètres de l'indexeur", 'docalist-search') ?></h1>

    <p class="description"><?php
        echo __(
            "Cette page vous permet de choisir les contenus de votre site
            qui serons disponibles dans votre moteur de recherche et de
            paramétrer le fonctionnement de l'indexeur.",
            'docalist-search'
        );
    ?></p>
    <p class="description"><?php
        printf(
            __(
                'Lorsque vous créez ou que vous modifiez des documents en série
                (lors d\'un import, par exemple) Docalist Search utilise un
                buffer dans lequel il stocke les documents à mettre à jour
                et à supprimer.

                Ce buffer permet <a href="%1$s">d\'optimiser l\'indexation des
                documents</a> en limitant le nombre de requêtes adressées à
                ElasticSearch, mais il consomme de la mémoire sur votre serveur
                et génère un délai avant que les nouveaux documents indexés
                n\'apparaissent dans vos résultats de recherche.

                Vous pouvez paramétrer le fonctionnement de ce buffer et fixer
                des limites sur la quantité de mémoire utilisée et le nombre de
                documents stockés dans le buffer.

                Dès que l\'une des deux limites est atteinte, le buffer est
                envoyé à ElasticSearch puis est réinitialisé.',
                'docalist-search'
            ),
            'http://www.elasticsearch.org/guide/reference/api/bulk/'    // %1
        );
    ?></p>

    <?php if ($error) :?>
        <div class="error">
            <p><?= $error ?></p>
        </div>
    <?php endif ?>

    <?php
        $form = new Form();
        $form->checklist('types')->setOptions($types);
        $form->input('bulkMaxSize')->setAttribute('type', 'number')->addClass('small-text');
        $form->input('bulkMaxCount')->setAttribute('type', 'number');//->addClass('regular-text');
        $form->checkbox('realtime');
        $form->submit(__('Enregistrer les modifications', 'docalist-search'))->addClass('button button-primary');

        $form->bind($settings)->display();
    ?>
</div>
