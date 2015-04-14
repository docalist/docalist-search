<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
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
 * @property Integer $connecttimeout Timeout de connexion, en secondes.
 * @property Integer $timeout Timeout des requêtes, en secondes.
 * @property Boolean $compressrequest Compresser les requêtes.
 * @property Boolean $compressresponse Compresser les réponses.
 */
class ServerSettings extends Object {
    // @formatter:off
    static protected function loadSchema() {
        global $wpdb;

        return [
            'fields' => [
                'url' => [
                    'label' =>__('Url de ElasticSearch', 'docalist-search'),
                    'description' => __("Url complète du moteur ElasticSearch à utiliser (par exemple : <code>http://127.0.0.1:9200</code>).", 'docalist-search'),
                    'default' => 'http://127.0.0.1:9200',
                ],
                'index' => [
                    'label' =>__("Nom de l'index à utiliser", 'docalist-search'),
                    'description' => __("Nom de l'index ElasticSearch qui contiendra tous les contenus indexés. <b>Attention</b> : vérifiez que cet index n'existe pas déjà.", 'docalist-search'),
                    // Par défaut : préfixe des tables + nom de la base (ex wp_prisme)
                    // Evite que deux sites sur le même serveur partagent par erreur le même index
                    'default' => $wpdb->get_blog_prefix() . DB_NAME,
                ],
                'connecttimeout' => [
                    'label' =>__('Timeout de connexion', 'docalist-search'),
                    'description' => __("Si la connexion avec le serveur ElasticSearch n'est pas établie au bout du nombre de secondes indiqué, une erreur sera générée.", 'docalist-search'),
                    'type' => 'int',
                    'default' => 1,
                ],
                'timeout' => [
                    'label' =>__('Timeout des requêtes', 'docalist-search'),
                    'description' => __("Si le serveur ElasticSearch n'a pas répondu au bout du nombre de secondes indiqué, une erreur sera générée.", 'docalist-search'),
                    'type' => 'int',
                    'default' => 10,
                ],
                'compressrequest' => [
                    'label' =>__('Compresser les requêtes', 'docalist-search'),
                    'description' => __("Compresse les requêtes envoyées au serveur. N'activez cette option que si votre serveur ElasticSearch sait décoder les requêtes compressées.", 'docalist-search'),
                    'type' => 'bool',
                    'default' => false,
                ],
                'compressresponse' => [
                    'label' =>__('Compresser les réponses', 'docalist-search'),
                    'description' => __("Demande à ElasticSearch de compresser les réponses retournées. Cette option n'a aucun effet si l'option <a href='http://www.elastic.co/guide/en/elasticsearch/reference/current/modules-http.html'>http.compression</a> n'est pas activée sur le serveur ElasticSearch.", 'docalist-search'),
                    'type' => 'bool',
                    'default' => false,
                ]
            ]
        ];
    }
    // @formatter:on
}