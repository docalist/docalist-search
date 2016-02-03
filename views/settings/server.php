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
use Docalist\Search\ServerSettings;
use Docalist\Forms\Form;

/**
 * Edite les paramètres du serveur Elastic Search.
 *
 * @var SettingsPage    $this
 * @var ServerSettings  $settings   Les paramètres du serveur.
 * @var string          $error      Erreur éventuelle à afficher.
 */
?>
<div class="wrap">
    <h1><?= __('Paramètres ElasticSearch', 'docalist-search') ?></h1>

    <p class="description"><?php
        //@formatter:off
        printf(
            __(
                'Pour fonctionner, Docalist Search doit pouvoir accéder à un cluster <a href="%1$s">ElasticSearch</a>
                dans lequel il va créer les index qui permettront de stocker et de retrouver vos contenus.
                ElasticSearch peut être <a href="%2$s">installé sur le serveur</a> qui héberge votre site web ou bien
                vous pouvez faire appel à un <a href="%3$s">service d\'hébergement dédié</a>.
                Cette page vous permet de spécifier l\'url de votre cluster ElasticSearch, le nom de l\'index à
                utiliser ainsi que d\'autres paramètres.',
                'docalist-search'
            ),
            'https://www.elastic.co/products/elasticsearch',                                // %1
            'https://www.elastic.co/guide/en/elasticsearch/reference/current/setup.html',   // %2
            'https://www.google.com/search?q=elasticsearch+hosting'                         // %3
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
        $form->input('url')->addClass('regular-text');
        $form->input('index')->addClass('regular-text');
        $form->input('connecttimeout')->setAttribute('type', 'number')->addClass('small-text');
        $form->input('timeout')->setAttribute('type', 'number')->addClass('small-text');
        $form->checkbox('compressrequest');
        $form->checkbox('compressresponse');
        $form->submit(__('Enregistrer les modifications', 'docalist-search'))->addClass('button button-primary');

        $form->bind($settings)->display();
    ?>
</div>
