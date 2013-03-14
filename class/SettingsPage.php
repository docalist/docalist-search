<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2012, 2013 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */
namespace Docalist\Search;
use Docalist\AbstractSettingsPage, Docalist\Forms\Fragment;

/**
 * Options de configuration du plugin.
 */
class SettingsPage extends AbstractSettingsPage {
    /**
     * @inheritdoc
     */
    protected function validate(&$settings) {

        $this->error('un message');
        $this->error('un autre message');
        $this->error('et encore un autre message');
    }

    /**
     * @inheritdoc
     */
    public function load() {
        $box = new Fragment();

        //@formatter:off

        $box->label(__('Docalist Search', 'docalist-search'))
            ->description('Modifiez les options ci-dessous. bla bla.')
//            ->attribute('notab', true)
            ;
//$box->input('test');
        $box->fieldset('General')
            ->name('general')
                ->checkbox('enabled')
                ->label('Activer la recherche');

        $box->fieldset('Serveur')
            ->description('description de l\'onglet serveur')
            ->name('server')
                ->input('url')
                ->addClass('large-text')
                ->label('Url du serveur ElasticSearch')
                ->description("Indiquez l'url du point d'entrée de l'API de votre server ElasticSearch. Si ElasticSearch est installé sur votre serveur, il s'agit en général d'une url de la forme <code>http://localhost:9200/</code>.")
            ->parent()
                ->input('index')
                ->label('Nom de l\'index')
                ->description("Nom de l'index (de la collection) qui sera créé dans ElasticSearch et qui contiendra tous les contenus indexés. <b>Attention :</b> vérifiez que cet index n'existe pas déjà.")
            ->parent()
                ->input('timeout')
                ->label('Timeout des requêtes<br />(en secondes)')
                ->description("Si le serveur ElasticSearch n'a pas répondu au bout du délai imparti, une erreur sera générée.")
                ;

        $box->fieldset('Contenus à indexer')
            ->name('content')
                ->checklist('posttypes')
                ->label('Indexer les post-types suivants')
                ->options(array('post','page'))
            ->parent()
                ->checkbox('comments')
                ->label('indexer les comentaires')
            ->parent()
                ->checkbox('users')
                ->label('indexer les utilisateurs')
            ;
/*
        $box->fieldset('articles');
        $box->fieldset('pages');
        $box->fieldset('notices');
        $box->fieldset('ressources');
        $box->fieldset('commentaires');
        $box->fieldset('utilisateurs');
        $box->fieldset('articles');
        $box->fieldset('pages');
        $box->fieldset('notices');
        $box->fieldset('ressources');
        $box->fieldset('commentaires');
        $box->fieldset('utilisateurs');
*/
        $this->form = $box;
    }
}
