<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2012, 2013 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package Docalist
 * @subpackage Search
 * @author Daniel Ménard <daniel.menard@laposte.net>
 * @version SVN: $Id$
 */
namespace Docalist\Search;

use Docalist\Data\Entity\AbstractEntity;

/**
 * Options de configuration du serveur ElasticSearch.
 *
 * @property string $url Url complète du serveur ElasticSearch.
 * @property string $index Nom de l'index ElasticSearch utilisé.
 * @property int $timeout Timeout des requêtes, en secondes.
 */
class ServerSettings extends AbstractEntity {
    // @formatter:off
    protected function loadSchema() {
        return array(
            'url' => array(
                'label' =>__('Url du serveur', 'docalist-search'),
                'description' => __("Url complète de votre server ElasticSearch (par exemple : <code>http://localhost:9200/</code>).", 'docalist-search'),
                'default' => 'http://127.0.0.1:9200/',
            ),
            'index' => array(
                'label' =>__("Nom de l'index ElasticSearch à créer", 'docalist-search'),
                'description' => __("Nom de l'index ElasticSearch qui contiendra tous les contenus indexés. <b>Attention</b> : vérifiez que cet index n'existe pas déjà.", 'docalist-search'),
                'default' => 'wordpress',
            ),
            'timeout' => array(
                'label' =>__('Timeout des requêtes, en secondes', 'docalist-search'),
                'description' => __("Si le serveur ElasticSearch n'a pas répondu au bout du délai imparti, une erreur sera générée.", 'docalist-search'),
                'type' => 'int',
                'default' => 10,
            ),
        );
    }
    // @formatter:on
}