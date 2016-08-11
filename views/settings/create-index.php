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
use Docalist\Search\Settings;
use Docalist\Forms\Form;
use Docalist\Search\Indexer;

/**
 * Créer/recréer l'index
 *
 * @var SettingsPage    $this
 * @var Settings        $settings   Les paramètres de docalist-search.
 * @var string          $error      Erreur éventuelle à afficher.
 * @var Indexer[]       $indexers   Liste des indexeurs disponibles.
 */

// Crée la liste des types disponibles (options de la checklist)
$types = [];
foreach($indexers as $indexer) {  /** @var Indexer $indexer */
    $types[$indexer->getCategory()][$indexer->getType()] = $indexer->getLabel();
}
?>
<div class="wrap">
    <h1><?= __("Créer l'index de recherche", 'docalist-search') ?></h1>

    <p class="description"><?php
        echo __(
            "Cette page vous permet de choisir les contenus de votre site qui seront disponibles dans votre moteur de
             recherche et de créer ou de recréer l'index en lançant une indexation de tous les contenus existants.",
            'docalist-search'
        );
    ?></p>

    <?php if ($error) :?>
        <div class="error">
            <p><?= $error ?></p>
        </div>
    <?php endif ?>

    <?php
        $form = new Form();

        // Choix des contenus à indexer
        $form->checklist('types')
             ->setOptions($types)
             ->setLabel(__('Choisissez les contenus à indexer', 'docalist-search'));

//         // Propose d'activer l'indexation en temps réelle si elle n'est pas encore activée
//         if (! $this->settings->realtime()) {
//             $form->checkbox('realtime')->setDescription(__(
//                  "Activer l'indexation en temps réel une fois l'index créé
//                   (l'index sera automatiquement mis à jour lorsque vos contenus seront créés, modifiés ou supprimés).",
//                  'docalist-search'
//              ));
//         }

        $form->submit(__("Créer l'index et indexer les contenus existants", 'docalist-search'))
             ->addClass('button button-primary');

        $form->bind($settings)->display('wordpress');
    ?>
    <h2><?=__('Remarques :', 'docalist-search')?></h2>
    <ul class="ul-square">
        <li><?=__(
            "L'indexation de vos contenus va prendre plus ou moins longtemps en fonction du nombre de documents à
             indexer et de la taille moyenne de vos contenus. Sur une installation \"moyenne\", docalist-search est
             capable d'indexer 20 à 30 000 documents à la minute.",
            'docalist-search')?>
        </li>
        <li><?=__(
            "Si votre index existe déjà, l'ancien index continuera à être utilisé en recherche pour éviter toute
             interruption de service sur votre site et un nouvel index sera créé pour procéder à l'indexation
             des nouveaux contenus. Une fois l'indexation terminée, l'ancien index sera remplacé par le nouvel
             index de façon atomique.",
            'docalist-search')?>
        </li>
        <?php if (! $this->settings->realtime()):?>
            <li><?=__(
                "L'indexation en temps réel sera automatiquement activée une fois l'index créé.",
                'docalist-search')?>
            </li>
        <?php endif; ?>
    </ul>
</div>
