<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2012-2014 Daniel Ménard
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

use Docalist\Type\Object;
use Docalist\Type\String;
use Docalist\Type\Integer;

/**
 * Options de configuration du serveur ElasticSearch.
 *
 * @property String $url Url complète du serveur ElasticSearch.
 * @property String $index Nom de l'index ElasticSearch utilisé.
 * @property Integer $timeout Timeout des requêtes, en secondes.
 */
class ServerSettings extends Object {
    // @formatter:off
    static protected function loadSchema() {
        global $wpdb;

        return [
            'fields' => [
                'url' => [
                    'label' =>__('Url de ElasticSearch', 'docalist-search'),
                    'description' => __("Url complète du moteur ElasticSearch à utiliser (par exemple : <code>http://127.0.0.1:9200/</code>).", 'docalist-search'),
                    'default' => 'http://127.0.0.1:9200/',
                ],
                'index' => [
                    'label' =>__("Nom de l'index à utiliser", 'docalist-search'),
                    'description' => __("Nom de l'index ElasticSearch qui contiendra tous les contenus indexés. <b>Attention</b> : vérifiez que cet index n'existe pas déjà.", 'docalist-search'),
                    // Par défaut : préfixe des tables + nom de la base (ex wp_prisme)
                    // Evite que deux sites sur le même serveur partagent par erreur le même index
                    'default' => $wpdb->get_blog_prefix() . DB_NAME,
                ],
                'timeout' => [
                    'label' =>__('Timeout des requêtes, en secondes', 'docalist-search'),
                    'description' => __("Si le serveur ElasticSearch n'a pas répondu au bout du délai imparti, une erreur sera générée.", 'docalist-search'),
                    'type' => 'int',
                    'default' => 10,
                ]
            ]
        ];
    }
    // @formatter:on
}