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

/**
 * Edite les paramètres du serveur Elastic Search.
 *
 * @var SettingsPage    $this
 * @var Settings        $settings   Les paramètres de docalist-search.
 */
?>
<div class="wrap">
    <h1><?= __('Paramètres Docalist-Search', 'docalist-search') ?></h1>

    <?php
        $form = new Form();

        $form->tag('h2.title', __('Cluster Elasticsearch', 'docalist-biblio'));
        $description = sprintf(
            __(
                'Pour fonctionner, Docalist Search doit pouvoir accéder à un cluster <a href="%1$s">Elasticsearch</a>
                dans lequel il va créer les index qui permettront de stocker et de retrouver vos contenus.

                Elasticsearch peut être <a href="%2$s">installé sur le serveur</a> qui héberge votre site web ou bien
                vous pouvez faire appel à un <a href="%3$s">service d\'hébergement dédié</a>.',
                'docalist-search'
            ),
            'https://www.elastic.co/products/elasticsearch',                                // %1
            'https://www.elastic.co/guide/en/elasticsearch/reference/current/setup.html',   // %2
            'https://www.google.com/search?q=elasticsearch+hosting'                         // %3
        );
        $form->tag('p.description', $description);
        $form->input('url')->addClass('regular-text');
        if (isset($settings->esversion) && $settings->esversion() !== '0.0.0') {
            $form->input('esversion')->addClass('small-text')->setAttribute('disabled');
        }
        $form->input('index')->addClass('regular-text');
        $form->input('shards')->setAttribute('type', 'number')->addClass('small-text');
        $form->input('replicas')->setAttribute('type', 'number')->addClass('small-text');


        $form->tag('h2.title', __("Délai d'attente", 'docalist-biblio'));
        $description = __(
            "Les options ci-dessous permettent de fixer une limite sur le temps de réponse de votre cluster
             Elasticsearch. En général, les options par défaut conviennent mais si vous utilisez un service
             Elasticsearch distant ou si votre cluster est un peu lent, vous pouvez augmenter un peu les délais.",
            'docalist-search'
        );
        $form->tag('p.description', $description);
        $form->input('connecttimeout')->setAttribute('type', 'number')->addClass('small-text');
        $form->input('timeout')->setAttribute('type', 'number')->addClass('small-text');


        $form->tag('h2.title', __('Compression du trafic réseau', 'docalist-biblio'));
        $description = __(
            "Les requêtes adressées au cluster Elasticsearch et les réponses retournées peuvent parfois être
             volumineuses. Si vous utilisez un cluster Elasticsearch distant, les options ci-dessous vous permettent
             d'activer la compression gzip et de diminuer le volume des données échangées.
             Remarque : si Elasticsearch est installé en local, c'est en général contre-productif d'activer ces
             options puisqu'il n'y a pas de traffic réseau.",
            'docalist-search'
        );
        $form->tag('p.description', $description);
        $form->checkbox('compressrequest');
        $form->checkbox('compressresponse');


        $form->tag('h2.title', __("Buffer d'indexation", 'docalist-biblio'));
        $description = __(
            "Lorsque vous créez ou que vous modifiez des documents Docalist Search utilise un buffer d'indexation dans
             lequel il stocke les documents à mettre à jour et à supprimer.

             Ce buffer permet d'optimiser l'indexation des documents en limitant le nombre de requêtes adressées au
             cluster Elasticsearch, mais il consomme de la mémoire sur votre serveur.

             Vous pouvez paramétrer le fonctionnement de ce buffer et fixer des limites sur la quantité de mémoire
             utilisée et le nombre de documents stockés dans le buffer.

             Dès que l'une des deux limites est atteinte, le buffer est envoyé à Elasticsearch puis est réinitialisé.",
            'docalist-search'
        );
        $form->tag('p.description', $description);

        $form->input('bulkMaxSize')->setAttribute('type', 'number')->addClass('small-text');
        $form->input('bulkMaxCount')->setAttribute('type', 'number');//->addClass('regular-text');

        // Propose de désactiver l'indexation en temps réelle si elle est activée
        $realtime = $form->checkbox('realtime');
        !$this->settings->realtime() && $realtime
            ->setAttribute('disabled')
            ->setDescription(__(
                "Cette option sera disponible une fois l'index créé.",
                'docalist-search'
            ));

        $form->submit(__('Enregistrer les modifications', 'docalist-search'))->addClass('button button-primary');

        $form->bind($settings)->display();
    ?>
</div>
