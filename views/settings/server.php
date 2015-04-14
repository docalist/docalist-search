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
 * @version     $Id$
 */
namespace Docalist\Search\Views;

use Docalist\Search\ServerSettings;
use Docalist\Forms\Form;

/**
 * Edite les paramètres du serveur Elastic Search.
 *
 * @param ServerSettings $settings Les paramètres du serveur.
 * @param string $error Erreur éventuelle à afficher.
 */
?>
<div class="wrap">
    <?= screen_icon() ?>
    <h2><?= __('Paramètres du serveur ElasticSearch', 'docalist-search') ?></h2>

    <p class="description"><?php
        //@formatter:off
        printf(
            __(
                'Pour fonctionner, Docalist Search doit pouvoir accéder à un
                moteur de recherche <a href="%1$s">ElasticSearch</a> dans
                lequel il va créer un index qui permettra de stocker et de
                retrouver vos contenus.
                ElasticSearch peut être <a href="%2$s">installé sur le serveur</a>
                qui héberge votre site web ou bien vous pouvez faire appel à un
                <a href="%3$s">service d\'hébergement dédié</a>.

                Cette page vous permet de spécifier l\'url de votre moteur
                ElasticSearch, le nom de l\'index à utiliser ainsi que d\'autres
                paramètres.',
                'docalist-search'
            ),
            'http://www.elasticsearch.org/',                            // %1
            'http://www.elasticsearch.org/guide/reference/setup/',      // %2
            'https://www.google.com/search?q=elasticsearch+hosting'     // %3
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
        $form->input('connecttimeout')->attribute('type', 'number')->addClass('small-text');
        $form->input('timeout')->attribute('type', 'number')->addClass('small-text');
        $form->checkbox('compressrequest');
        $form->checkbox('compressresponse');
        $form->submit(__('Enregistrer les modifications', 'docalist-search'));

        $form->bind($settings)->render('wordpress');
    ?>
</div>