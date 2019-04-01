<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Search;

use Docalist\Type\Settings as TypeSettings;
use Docalist\Type\Text;
use Docalist\Type\Integer;
use Docalist\Type\Boolean;
use Docalist\Type\Composite;
use Docalist\Type\Collection;

/**
 * Options de configuration du plugin.
 *
 * @property Text       $url                    Url complète du serveur Elasticsearch.
 * @property Text       $esversion              Numéro de version de Elasticsearch.
 * @property Text       $index                  Nom de l'index Elasticsearch utilisé.
 * @property Integer    $shards                 Nombre de shards de l'index.
 * @property Integer    $replicas               Nombre de replicas par shard.
 * @property Integer    $connecttimeout         Timeout de connexion, en secondes.
 * @property Integer    $timeout                Timeout des requêtes, en secondes.
 * @property Boolean    $compressrequest        Compresser les requêtes.
 * @property Boolean    $compressresponse       Compresser les réponses.
 * @property Text[]     $types                  Contenus à indexer.
 * @property Integer    $bulkMaxSize            Taille maximale du buffer (Mo).
 * @property Integer    $bulkMaxCount           Nombre maximum de documents dans le buffer.
 * @property Boolean    $realtime               Indexation en temps réel activée.
 * @property Integer    $searchpage             ID de la page "liste des réponses".
 * @property Boolean    $enabled                Indique si la recherche est activée.
 * @property Collection $defaultSearchFields    Champs de recherche par défaut.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Settings extends TypeSettings
{
    protected $id = 'docalist-search-settings';

    public static function loadSchema(): array
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
                    'type' => Text::class,
                    'label' => __('Url du cluster Elasticsearch', 'docalist-search'),
                    'description' => __(
                        "Adresse complète de votre cluster Elasticsearch (exemple : <code>http://127.0.0.1:9200</code>).
                         Vous devez indiquer le protocole utilisé (http ou https), l'adresse IP ou le nom DNS du
                         cluster et le port TCP à utiliser s'il est spécifique.
                         Si votre cluster est protégé par un login et un mot de passe, indiquez-les en utilisant la
                        syntaxe suivante : <code>https://user:password@search.example.org:9200</code>.
                        ",
                        'docalist-search'
                    ),
                    'default' => 'http://127.0.0.1:9200',
                ],
                'esversion' => [
                    'type' => Text::class,
                    'label' => __("Version de Elasticsearch", 'docalist-search'),
                    'description' => __(
                        "Numéro de version retourné par votre cluster Elasticsearch lorsque vos paramètres ont été
                         enregistrés pour la dernière fois. Si votre cluster change de version, ré-enregistrez vos
                         paramètres.",
                        'docalist-search'
                    ),
                    'default' => '0.0.0',
                ],
                'index' => [
                    'type' => Text::class,
                    'label' => __("Nom de base de l'index", 'docalist-search'),
                    'description' => __(
                        "Préfixe qui sera utilisé pour déterminer le nom des index et des alias Elasticsearch
                         qui contiendront les contenus indexés. Assurez-vous que le préfixe indiqué soit unique :
                         les index ne doivent pas être partagés entre plusieurs sites docalist.",
                        'docalist-search'
                    ),
                    'default' => $defaultIndex,
                ],
                'shards' => [
                    'type' => Integer::class,
                    'label' => __('Nombre de shards', 'docalist-search'),
                    'description' => sprintf(
                        __(
                            "Elasticsearch permet de <a href='%s'>partitionner un index en plusieurs shards</a>
                            (segments) et de distribuer ces segments sur les différents noeuds qui composent le
                            cluster. Indiquez le nombre de shards à créer en fonction du nombre de noeuds dans
                            votre cluster et de la taille des données que vous prévoyez d'indexer.",
                            'docalist-search'
                        ),
                        'https://www.elastic.co/guide/en/elasticsearch/guide/master/shard-scale.html'
                    ),
                    'default' => 1,
                ],
                'replicas' => [
                    'type' => Integer::class,
                    'label' => __('Nombre de réplicas', 'docalist-search'),
                    'description' => sprintf(
                        __(
                            "Pour <a href='%s'>augmenter la tolérance aux pannes</a>, Elasticsearch peut créer
                            des copies des shards (des réplicats) et les répartir sur les noeuds du cluster.
                            Ces copies permettent à Elasticsearch de continuer à fonctionner si un noeud tombe
                            en panne et augmente le nombre de requêtes simultanées qui pourront être traitées.
                            Indiquez le nombre de réplicats à créer en fonction du nombre de noeuds présents
                            dans votre cluster (zéro si vous n'avez qu'un seul serveur).",
                            'docalist-search'
                        ),
                        'https://www.elastic.co/guide/en/elasticsearch/reference/master/' .
                        '_basic_concepts.html#_shards_amp_replicas'
                    ),
                    'default' => 0,
                ],
                'connecttimeout' => [
                    'type' => Integer::class,
                    'label' => __('Timeout de connexion', 'docalist-search'),
                    'description' => __(
                        "En secondes. Si la connexion avec le cluster Elasticsearch n'est pas établie au bout
                         du nombre de secondes indiqué, une erreur sera générée.",
                        'docalist-search'
                    ),
                    'default' => 1,
                ],
                'timeout' => [
                    'type' => Integer::class,
                    'label' => __('Timeout des requêtes', 'docalist-search'),
                    'description' => __(
                        "En secondes. Si le serveur Elasticsearch n'a pas répondu au bout du nombre
                         de secondes indiqué, une erreur sera générée.",
                        'docalist-search'
                    ),
                    'default' => 10,
                ],
                'compressrequest' => [
                    'type' => Boolean::class,
                    'label' => __('Compresser les requêtes', 'docalist-search'),
                    'description' => __(
                        "Compresse les requêtes envoyées au serveur. N'activez cette option que si votre serveur
                         Elasticsearch sait décoder les requêtes compressées.",
                        'docalist-search'
                    ),
                    'default' => false,
                ],
                'compressresponse' => [
                    'type' => Boolean::class,
                    'label' => __('Compresser les réponses', 'docalist-search'),
                    'description' => sprintf(
                        __(
                            "Demande à Elasticsearch de compresser les réponses retournées. Cette option n'a
                            aucun effet si l'option <a href='%s'>http.compression</a> n'est pas activée sur
                            le serveur Elasticsearch.",
                            'docalist-search'
                        ),
                        'http://www.elastic.co/guide/en/elasticsearch/reference/current/modules-http.html'
                    ),
                    'default' => false,
                ],

                /*
                 * Paramètres d'indexation
                 */
                'types' => [
                    'type' => Text::class,
                    'repeatable' => true,
                    'label' => __('Contenus à indexer', 'docalist-search'),
                ],
                'bulkMaxSize' => [
                    'type' => Integer::class,
                    'label' => __('Taille maximale du buffer', 'docalist-search'),
                    'description' => __(
                        'En méga-octets. Le buffer est vidé si la taille totale des documents en attente
                        dépasse cette limite.',
                        'docalist-search'
                    ),
                    'default' => 10,
                ],
                'bulkMaxCount' => [
                    'type' => Integer::class,
                    'label' => __('Nombre maximum de documents', 'docalist-search'),
                    'description' => __(
                        'Le buffer est vidé si le nombre de documents en attente dépasse ce nombre.',
                        'docalist-search'
                    ),
                    'default' => 10000,
                ],
                'realtime' => [
                    'type' => Boolean::class,
                    'label' => __('Indexation en temps réel', 'docalist-search'),
                    'description' => __(
                        'Réindexer automatiquement les contenus créés ou modifiés et retirer les
                        contenus supprimés.',
                        'docalist-search'
                    ),
                    'default' => false,
                ],

                /*
                 * Paramètres généraux
                 */
                'searchpage' => [
                    'type' => Integer::class,
                    'label' => __('Page liste des réponses', 'docalist-search'),
                    'description' => __(
                        'Page WordPress sur laquelle sont affichées les réponses obtenues.',
                        'docalist-search'
                    ),
                ],
                'enabled' => [
                    'type' => Boolean::class,
                    'label' => __('Recherche Docalist Search', 'docalist-search'),
                    'description' => __('Activer la recherche Docalist Search.', 'docalist-search'),
                    'default' => false,
                ],

                /*
                 * Champs par défaut et pondération
                 */
                'defaultSearchFields' => [
                    'type' => Composite::class,
                    'label' => __('Champs de recherche par défaut', 'docalist-search'),
                    'description' => __(
                        "Liste des champs sur lesquels portera la recherche si la requête de l'utilisateur ne
                        mentionne aucun champ. Pour ajuster la pertinence des réponses obtenues, vous pouvez
                        indiquer pour chaque champ un poids (>= 1) qui traduit l'importance de ce champ par
                        rapport aux autres (exemple : title=2, content=1, topic=5).",
                        'docalist-search'
                    ),
                    'repeatable' => true,
                    'fields' => [
                        'field' => [
                            'type' => Text::class,
                            'label' => __('Champ', 'docalist-search'),
                        ],
                        'weight' => [
                            'type' => Integer::class,
                            'label' => __('Poids', 'docalist-search'),
                        ],
                    ],

                ],
            ],
        ];
    }

    /**
     * Annule le chargement des setting s'ils sont trop anciens et génère une admin notice.
     */
    public function assign($value): void
    {
        if (isset($value['server']) || isset($value['indexer'])) {
            if (is_admin() && !wp_doing_ajax()) {
                docalist('admin-notices')->error(
                    'Vos paramètres docalist-search sont trop anciens et ne peuvent pas être utilisés.
                     Allez sur la page réglages docalist-search pour paramétrer à nouveau le moteur de recherche.',
                    'docalist-search'
                );

            }
            $value = $this->getDefaultValue();
        }

        parent::assign($value);
    }

    /**
     * Retourne la liste des champs de recherche par défaut avec leur poids respectifs.
     *
     * @return string[] Un tableau de la forme ['posttitle^2', 'content', 'name', 'topic^5'].
     */
    public function getDefaultSearchFields(): array
    {
        $fields = [];
        foreach ($this->defaultSearchFields as $field) {
            $name = $field->field->getPhpValue();
            $weight = $field->weight->getPhpValue();
            $weight > 1 && $name .= '^' . $weight;
            $fields[] = $name;
        }
        return $fields;
    }
}
