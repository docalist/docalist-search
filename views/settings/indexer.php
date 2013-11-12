<?php
/**
 * This file is part of the 'Docalist Search' plugin.
 *
 * Copyright (C) 2012, 2013 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     $Id$
 */
namespace Docalist\Search\Views;

use Docalist\Search\IndexerSettings;
use Docalist\Forms\Form;

/**
 * Paramètres de l'indexeur.
 *
 * @param IndexerSettings $settings Les paramètres de l'indexeur.
 * @param string $error Erreur éventuelle à afficher.
 * @param string[] $types Liste des types disponibles.
 */
?>
<div class="wrap">
    <?= screen_icon() ?>
    <h2><?= __("Paramètres de l'indexeur", 'docalist-search') ?></h2>

    <p class="description"><?php
        //@formatter:off
        echo __(
            "Cette page vous permet de choisir les contenus de votre site
            qui serons disponibles dans votre moteur de recherche et de
            paramétrer le fonctionnement de l'indexeur.",
            'docalist-search'
        );
        // @formatter:on
    ?></p>
    <p class="description"><?php
        //@formatter:off
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
        // @formatter:on
    ?></p>

    <?php if ($error) :?>
        <div class="error">
            <p><?= $error ?></p>
        </div>
    <?php endif ?>

    <?php
        $form = new Form();
        $form->checklist('types')->options($types);
        $form->input('bulkMaxSize');
        $form->input('bulkMaxCount');
        $form->submit(__('Enregistrer les modifications', 'docalist-search'));

        $form->bind($settings)->render('wordpress');
    ?>
</div>