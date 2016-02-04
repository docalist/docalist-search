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
 */
namespace Docalist\Search;

use Docalist\Type\Settings as TypeSettings;
use Docalist\Type\Integer;
use Docalist\Type\Boolean;

/**
 * Options de configuration du plugin.
 *
 * @property Integer            $searchpage ID de la page "liste des réponses".
 * @property Boolean            $enabled    Indique si la recherche est activée.
 * @property ServerSettings     $server     Paramètres du serveur ElasticSearch.
 * @property IndexerSettings    $indexer    Paramètres de l'indexeur.
 */
class Settings extends TypeSettings
{
    protected $id = 'docalist-search-settings';

    static public function loadSchema()
    {
        // Nom par défaut de l'index : préfixe des tables wordpress + nom de la base (ex wp_prisme)
        // Evite que deux sites sur le même serveur partagent par erreur le même index
        $defaultIndex = docalist('wordpress-database')->get_blog_prefix() . DB_NAME;

        return [
            'label' => 'Paramètres docalist-search',
            'fields' => [
                /*
                 * Paramètres serveur
                 */
                'url' => [
                    'type' => 'Docalist\Type\Text',
                    'label' => __('Url du cluster ElasticSearch', 'docalist-search'),
                    'description' => __(
                        "Adresse complète de votre cluster ElasticSearch (exemple : <code>http://127.0.0.1:9200</code>).
                         Vous devez indiquer le protocole utilisé (http ou https), l'adresse IP ou le nom DNS du
                         cluster et le port TCP à utiliser s'il est spécifique.
                         Si votre cluster est protégé par un login et un mot de passe, indiquez-les en utilisant la
                        syntaxe suivante : <code>https://user:password@search.example.org:9200</code>.
                        ",
                        'docalist-search'
                    ),
                    'default' => 'http://127.0.0.1:9200',
                ],
                'index' => [
                    'type' => 'Docalist\Type\Text',
                    'label' => __("Nom de base de l'index", 'docalist-search'),
                    'description' => __(
                        "Préfixe qui sera utilisé pour déterminer le nom des index et des alias ElasticSearch
                         qui contiendront les contenus indexés. Assurez-vous que le préfixe indiqué soit unique :
                         les index ne doivent pas être partagés entre plusieurs sites docalist.",
                        'docalist-search'
                    ),
                    'default' => $defaultIndex,
                ],
                'connecttimeout' => [
                    'type' => 'Docalist\Type\Integer',
                    'label' => __('Timeout de connexion', 'docalist-search'),
                    'description' => __(
                        "En secondes. Si la connexion avec le cluster ElasticSearch n'est pas établie au bout
                         du nombre de secondes indiqué, une erreur sera générée.",
                        'docalist-search'
                    ),
                    'default' => 1,
                ],
                'timeout' => [
                    'type' => 'Docalist\Type\Integer',
                    'label' => __('Timeout des requêtes', 'docalist-search'),
                    'description' => __(
                        "En secondes. Si le serveur ElasticSearch n'a pas répondu au bout du nombre
                         de secondes indiqué, une erreur sera générée.",
                        'docalist-search'
                    ),
                    'default' => 10,
                ],
                'compressrequest' => [
                    'type' => 'Docalist\Type\Boolean',
                    'label' => __('Compresser les requêtes', 'docalist-search'),
                    'description' => __(
                        "Compresse les requêtes envoyées au serveur. N'activez cette option que si votre serveur
                         ElasticSearch sait décoder les requêtes compressées.",
                        'docalist-search'
                    ),
                    'default' => false,
                ],
                'compressresponse' => [
                    'type' => 'Docalist\Type\Boolean',
                    'label' => __('Compresser les réponses', 'docalist-search'),
                    'description' => sprintf(__(
                        "Demande à ElasticSearch de compresser les réponses retournées. Cette option n'a aucun effet
                         si l'option <a href='%s'>http.compression</a> n'est pas activée sur le serveur ElasticSearch.",
                        'docalist-search'),
                        'http://www.elastic.co/guide/en/elasticsearch/reference/current/modules-http.html'
                    ),
                    'default' => false,
                ],

                /*
                 * Paramètres d'indexation
                 */
                'types' => [
                    'type' => 'Docalist\Type\Text*',
                    'label' => __('Contenus à indexer', 'docalist-search'),
                ],
                'bulkMaxSize' => [
                    'type' => 'Docalist\Type\Integer',
                    'label' => __('Taille maximale du buffer', 'docalist-search'),
                    'description' => __('En méga-octets. Le buffer est vidé si la taille totale des documents en attente dépasse cette limite.', 'docalist-search'),
                    'default' => 10,
                ],
                'bulkMaxCount' => [
                    'type' => 'Docalist\Type\Integer',
                    'label' => __('Nombre maximum de documents', 'docalist-search'),
                    'description' => __('Le buffer est vidé si le nombre de documents en attente dépasse ce nombre.', 'docalist-search'),
                    'default' => 10000,
                ],
                'realtime' => [
                    'type' => 'Docalist\Type\Boolean',
                    'label' => __('Indexation en temps réel', 'docalist-search'),
                    'description' => __('Réindexer automatiquement les contenus créés ou modifiés et retirer les contenus supprimés.', 'docalist-search'),
                    'default' => false,
                ],

                /*
                 * Paramètres généraux
                 */
                'searchpage' => [
                    'type' => 'Docalist\Type\Integer',
                    'label' => __('Page liste des réponses', 'docalist-search'),
                    'description' => __('Page WordPress sur laquelle sont affichées les réponses obtenues.', 'docalist-search'),
                ],
                'enabled' => [
                    'type' => 'Docalist\Type\Boolean',
                    'label' => __('Recherche Docalist Search', 'docalist-search'),
                    'description' => __('Activer la recherche Docalist Search.', 'docalist-search'),
                    'default' => false,
                ],
            ],
        ];
    }
}
