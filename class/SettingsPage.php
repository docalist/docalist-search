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

use Docalist\AdminPage;
use Docalist\Http\CallbackResponse;
use InvalidArgumentException;
use Exception;

/**
 * Options de configuration du plugin.
 */
class SettingsPage extends AdminPage
{
    /**
     * Paramètres de docalist-search.
     *
     * @var Settings
     */
    protected $settings;

    /**
     * @param Settings $settings Paramètres de docalist-search.
     */
    public function __construct(Settings $settings)
    {
        $this->settings = $settings;

        // @formatter:off
        parent::__construct(
            'docalist-search-settings',                       // ID
            'options-general.php',                            // page parent
            __('Docalist Search', 'docalist-search')          // libellé menu
        );
        // @formatter:on

        // Ajoute un lien "Réglages" dans la page des plugins
        $filter = 'plugin_action_links_docalist-search/docalist-search.php';
        add_filter($filter, function ($actions) {
            $action = sprintf(
                    '<a href="%s" title="%s">%s</a>',
                    esc_attr($this->url()),
                    $this->menuTitle(),
                    __('Réglages', 'docalist-biblio')
            );
            array_unshift($actions, $action);

            return $actions;
        });
    }

    /**
     * Page d'accueil (menu) des réglages Docalist-Search.
     *
     * @return ViewResponse
     */
    public function actionIndex()
    {
        return $this->view('docalist-search:settings/index');
    }

    /**
     * Paramètres du serveur ElasticSearch.
     *
     * @return ViewResponse
     */
    public function actionServerSettings()
    {
        $settings = $this->settings;

        if ($this->isPost()) {
            try {
                $_POST = wp_unslash($_POST);
                $settings->url = rtrim($_POST['url'], '/');
                $settings->index = $_POST['index'];
                $settings->shards = (int) $_POST['shards'];
                $settings->replicas = (int) $_POST['replicas'];
                $settings->connecttimeout = (int) $_POST['connecttimeout'];
                $settings->timeout = (int) $_POST['timeout'];
                $settings->compressrequest = (bool) $_POST['compressrequest'];
                $settings->compressresponse = (bool) $_POST['compressresponse'];
                $settings->bulkMaxSize = (int) $_POST['bulkMaxSize'];
                $settings->bulkMaxCount = (int) $_POST['bulkMaxCount'];
                if (isset($_POST['realtime']) && $_POST['realtime'] === '') {
                    $settings->realtime = false;
                }

                $this->validateSettings();

                $this->settings->save();

                return $this->redirect($this->url('Index'), 303);
            } catch (Exception $e) {
                docalist('admin-notices')->error($e->getMessage(), __('Erreur dans vos paramètres', 'docalist-search'));
            }
        }

        return $this->view('docalist-search:settings/server', ['settings' => $settings]);
    }

    /**
     * Valide les settings.
     *
     * @throws InvalidArgumentException en cas d'erreur.
     */
     protected function validateSettings()
     {
         // Vérifie qu'on a une url
         $url = $this->settings->url();
         if (empty($url)) {
             throw new InvalidArgumentException(
                 __("Vous devez indiquer l'url du cluster elasticsearch.", 'docalist-search')
             );
         }

        // Stocke le numéro de version de elasticsearch
         $version = docalist('elastic-search')->getVersion();
         if (is_null($version)) {
             throw new InvalidArgumentException(
                 __("Impossible d'obtenir la version de elasticsearch, verifiez l'url indiquée.", 'docalist-search')
             );
        }
        $this->settings->esversion = $version;
     }

    /**
     * Est-ce que le serveur ES répond ?
     *
     * Indique si le serveur répond et teste si l'index existe.
     */
    public function actionServerStatus()
    {
        /** @var IndexManager $indexManager */
        $indexManager = docalist('docalist-search-index-manager');

        switch ($indexManager->ping()) {
            case 0:
                $msg = __("L'url %s ne répond pas.", 'docalist-search');

                return printf($msg,
                    $this->settings->url()
                );
            case 1:
                $msg = __("Le serveur Elastic Search répond à l'url %s. L'index %s n'existe pas.", 'docalist-search');

                return printf($msg,
                    $this->settings->url(),
                    $this->settings->index()
                );
            case 2:
                $msg = __("Le serveur Elastic Search répond à l'url %s. L'index %s existe.", 'docalist-search');

                return printf($msg,
                    $this->settings->url(),
                    $this->settings->index()
                );
        }

        // Etat du cluster pour l'index indiqué (status green, etc.)
        // http://localhost:9200/_cluster/health/wp_prisme?pretty
        // @see http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/cluster-health.html

        // Statut de l'index (taille, nb de docs,
        // http://localhost:9200/wp_prisme/_status?pretty
        // @see http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/indices-status.html

        // Stats sur les opérations effectuées sur l'index
        // http://localhost:9200/wp_prisme/_stats?pretty
    }

    /**
     * Crée (ou recrée) l'index.
     *
     * @param array $types Liste des contenus à indexer.
     *
     * @return CallbackResponse
     */
    public function actionCreateIndex($types = null)
    {
        $settings = $this->settings;
        $error = '';

        // Permet à l'utilisateur de choisir les types à indexer
        if (is_null($types)) {
            $this->isPost() && $error = __(
                'Vous devez sélectionner au moins un type de contenu à indexer.',
                'docalist-search'
            );

            return $this->view('docalist-search:settings/create-index', [
                'settings' => $settings,
                'error' => $error,
                'indexers' => docalist('docalist-search-index-manager')->getAvailableIndexers(),
            ]);
        }

        // Enregistre les types choisis dans les settings
        $settings->types = $types;
        $this->settings->save();

        // On retourne une réponse de type "callback" qui va lancer la création de l'index et l'indexation
        $response = new CallbackResponse(function () {
            // Supprime la bufferisation pour voir le suivi en temps réel
            while (ob_get_level()) {
                ob_end_flush();
            }

            // Pour suivre le déroulement de l'indexation, on affiche une vue qui installe différents filtres sur les
            // événements déclenchés par l'indexeur.
            $this->view('docalist-search:settings/reindex')->sendContent();

            // Lance la réindexation
            $indexManager = docalist('docalist-search-index-manager'); /** @var IndexManager $indexManager */
            $indexManager->createIndex();
        });

        // Indique que notre réponse doit s'afficher dans le back-office wp
        $response->adminPage(true);

        // Terminé
        return $response;
    }

    /**
     * Paramètres du moteur de recherche.
     *
     * Permet entres autres d'activer la recherche.
     *
     * @return ViewResponse
     */
    public function actionSearchSettings()
    {
        // Teste si la recherche peut être activée
        $error = '';
        if (! $this->settings->enabled()) {
            /** @var IndexManager $indexManager */
            $indexManager = docalist('docalist-search-index-manager');
            $ping = $indexManager->ping();

            // 0. ES ne répond pas
            if ($ping === 0) {
                $msg = __('Vérifiez les <a href="%s">paramètres du serveur</a>.', 'docalist-search');
                $msg = sprintf($msg, esc_url($this->url('ServerSettings')));

                return $this->view('docalist-core:error', [
                    'h2' => __('Paramètres de recherche', 'docalist-biblio'),
                    'h3' => __('Le serveur ElasticSearch ne répond pas', 'docalist-biblio'),
                    'message' => $msg,
                ]);
            }

            // 1. ES répond mais l'index n'existe pas encore
            if ($ping === 1) {
                $msg = __('Vérifiez les <a href="%s">paramètres de l\'indexeur</a>.', 'docalist-search');
                $msg = sprintf($msg, esc_url($this->url('IndexerSettings')));

                return $this->view('docalist-core:error', [
                    'h2' => __('Paramètres de recherche', 'docalist-biblio'),
                    'h3' => __("L'index ElasticSearch n'existe pas.", 'docalist-biblio'),
                    'message' => $msg,
                ]);
            }

            // 2. ES répond et l'index existe, vérifie que l'index n'est pas vide
            $response = docalist('elastic-search')->get('/{index}/_count');
            if (!isset($response->count) || $response->count === 0) {
                $msg = __('Lancez une <a href="%s">réindexation manuelle</a> de vos contenus.', 'docalist-search');
                $msg = sprintf($msg, esc_url($this->url('Reindex')));

                return $this->view('docalist-core:error', [
                    'h2' => __('Paramètres de recherche', 'docalist-biblio'),
                    'h3' => __("L'index ElasticSearch ne contient aucun document.", 'docalist-biblio'),
                    'message' => $msg,
                ]);
            }
        }

        if ($this->isPost()) {
            $_POST = wp_unslash($_POST);
            $this->settings->searchpage = (int) $_POST['searchpage'];
            $this->settings->enabled = (bool) $_POST['enabled'];

            // $settings->validate();
            $this->settings->save();

            return $this->redirect($this->url('Index'), 303);
        }

        return $this->view('docalist-search:settings/search', [
            'settings' => $this->settings,
            'error' => $error,
        ]);
    }

    protected function getAllFields()
    {
        // On fait une recherche * en demandant une agrégation sur le champ spécial _field_names
        // cf. https://www.elastic.co/guide/en/elasticsearch/reference/master/mapping-field-names-field.html
        $response = docalist('elastic-search')->get('/{index}/_search', [
            'size' => 0,
            'aggs' => [
                'fields' => [ // Nom de l'agrégation générée
                    'terms' => [
                        'field' => '_field_names',
                        'size' => 0, // 0 = pas de limit (INT_MAX)
                        'order' => [ '_term' => 'asc' ],
                    ],
                ],
            ]
        ]);

        $fields = [];
        foreach($response->aggregations->fields->buckets as $bucket) {
            $field = $bucket->key;
            // On ne peut avoir de "field data" pour le champ source, ni pour un champ de type "completion"
            if ($field === '_source' || substr($field, -8) === '.suggest') {
                continue;
            }
            $fields[] = $field;
        }

        return $fields;
    }

    public function actionFieldData($query = '*')
    {
        $response = docalist('elastic-search')->get('/{index}/_search', [
            'query' => [
                'query_string' => [
                    'query' => $query,
                ],
            ],
            'fielddata_fields' => $this->getAllFields(),
        ]);

        return $this->view('docalist-search:debug/field-data', [
            'query' => $query,
            'response' => $response
        ]);
    }
}
