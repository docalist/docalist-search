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

use Docalist\AdminPage;
use Docalist\Forms\Form;
use Docalist\Http\CallbackResponse;

/**
 * Options de configuration du plugin.
 */
class SettingsPage extends AdminPage {
    /**
     *
     * @var Settings
     */
    protected $settings;

    /**
     *
     * @param Settings $settings
     */
    public function __construct(Settings $settings) {
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

    public function actionIndex() {
        return $this->view('docalist-search:settings/index');
    }

    /**
     * Paramètres du serveur ElasticSearch.
     */
    public function actionServerSettings() {
        $settings = $this->settings->server;

        $error = '';
        if ($this->isPost()) {
            try {
                $_POST = wp_unslash($_POST);
                $settings->url = $_POST['url'];
                $settings->index = $_POST['index'];
                $settings->timeout = $_POST['timeout'];

                // $settings->validate();
                $this->settings->save();

                return $this->redirect($this->url('Index'), 303);
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }

        return $this->view('docalist-search:settings/server', [
            'settings' => $settings,
            'error' => $error
        ]);
    }

    /**
     * Est-ce que le serveur ES répond ?
     *
     * Indique si le serveur répond et teste si l'index existe.
     */
    public function actionServerStatus() {
        /* @var $indexer Indexer */
        $indexer = docalist('docalist-search-indexer');

        switch($indexer->ping()) {
            case 0:
                $msg = __("L'url %s ne répond pas.", 'docalist-search');
                return printf($msg,
                    $this->settings->server->url()
                );
            case 1:
                $msg = __("Le serveur Elastic Search répond à l'url %s. L'index %s n'existe pas.", 'docalist-search');
                return printf($msg,
                    $this->settings->server->url(),
                    $this->settings->server->index()
                );
            case 2:
                $msg = __("Le serveur Elastic Search répond à l'url %s. L'index %s existe.", 'docalist-search');
                return printf($msg,
                    $this->settings->server->url(),
                    $this->settings->server->index()
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
     * Paramètres de l'indexeur.
     */
    public function actionIndexerSettings() {
        $settings = $this->settings->indexer;

        $error = '';
        if ($this->isPost()) {
            try {
                $_POST = wp_unslash($_POST);
                $settings->types = $_POST['types'];
                $settings->bulkMaxSize = $_POST['bulkMaxSize'];
                $settings->bulkMaxCount = $_POST['bulkMaxCount'];

                // $settings->validate();
                $this->settings->save();

                // crée l'index, les mappings, etc.
                /* @var $indexer Indexer */
                $indexer = docalist('docalist-search-indexer');
                $indexer->setup();

                return $this->redirect($this->url('Index'), 303);
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }

        return $this->view('docalist-search:settings/indexer', [
            'settings' => $settings,
            'error' => $error,
            'types' => $this->availableTypes()
        ]);
    }

    /**
     * Paramètres du moteur de recherche.
     *
     * Permet entres autres d'activer la recherche.
     *
     * @return boolean
     */
    public function actionSearchSettings() {

        // Teste si la recherche peut être activée
        $error = '';
        if (! $this->settings->enabled()) {
            /* @var $indexer Indexer */
            $indexer = docalist('docalist-search-indexer');
            $ping = $indexer->ping();

            // 0. ES ne répond pas
            if ($ping === 0) {
                $msg = __('Vérifiez les <a href="%s">paramètres du serveur</a>.', 'docalist-search');
                $msg = sprintf($msg, esc_url($this->url('ServerSettings')));
                return $this->view('docalist-core:error', [
                    'h2' => __('Paramètres de recherche', 'docalist-biblio'),
                    'h3' => __("Le serveur ElasticSearch ne répond pas", 'docalist-biblio'),
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
            $response = docalist('elastic-search')->get('_count');
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
            $this->settings->enabled = (bool) $_POST['enabled'];

            // $settings->validate();
            $this->settings->save();

            return $this->redirect($this->url('Index'), 303);
        }

        return $this->view('docalist-search:settings/search', [
            'settings' => $this->settings,
            'error' => $error
        ]);
    }

    /**
     * Gestion des synonymes
     *
     * todo : pouvoir définir les synonymes qu'on veut utiliser pour les champs
     *
     * autre p
     */
    // public function actionSynonyms() {}

    /**
     * Fichiers logs
     *
     * todo : logs des recherches, lors des indexations, slowlog...
     */
    // public function actionLogs() {}

    /**
     * Avancé.
     * Paramétrage des mappings
     *
     * todo : édition du json des mappings d'un type donné. Utile ?
     */
    // public function actionMappings() {}

    /**
     * Avancé.
     * Paramétrage des mots vides
     *
     * todo : à voir. Dernière version de ES, plus besoin de mots vides.
     */
    // public function actionStopwords() {}

    /**
     * Réindexer la base
     *
     * Permet de lancer une réindexation complète des collections en
     * choisissant les types de documents à réindexer.
     *
     * @param array $types Les types à réindexer
     */
    public function actionReindex($selected = null) {
        // Permet à l'utilisateur de choisir les types à réindexer
        if (empty($selected)) {
            // Parmi ceux qui sont indexés
            $types = $this->settings->indexer->types();
            $types = array_flip($types);
            $types = array_intersect_key($this->availableTypes(), $types);

            return $this->view('docalist-search:settings/reindex-choose', [
                'types' => $types,
            ]);
        }

        // On va retourner une réponse de type "callback" qui va lancer la
        // réindexation à proprement parler lorsqu'elle sera générée.
        $response = new CallbackResponse(function() use($selected) {

            // Supprime la bufferisation pour voir le suivi en temps réel
            while(ob_get_level()) ob_end_flush();

            // Pour suivre le déroulement de la réindexation, on affiche
            // une vue qui installe différents filtres sur les événements
            // déclenchés par l'indexeur.
            $this->view('docalist-search:settings/reindex')->sendContent();

            // Lance la réindexation
            docalist('docalist-search-indexer')->reindex($selected);
        });

        // Indique que notre réponse doit s'afficher dans le back-office wp
        $response->adminPage(true);

        // Terminé
        return $response;
    }

    /**
     * Valide les options saisies.
     *
     * @return string Message en cas d'erreur.
     */

    /*
     * protected function validateSettings() { }
     *  todo : - tester si l'url indiquée pour le server est correcte / répond
     *  - faire une action ajax qui prend en paramètre l'url et
     *    répond true ou un message d'erreur
     *
     *  - ajouter un javascript qui fait enabled.onchange = appeller l'url et
     *    mettre un message à coté de la zone de texte (ok, pas ok).
     *
     *  - faire la même chose dès le chargement de la page (comme ça quand on
     *    va sur la page, on sait tout de suite si le serveur est ok ou pas).
     *
     *  - tester si l'index indiqué existe déjà ou pas - ne fait quelque chose
     *    que si on sait que le serveur répond
     *
     *  - faire une action ajax qui teste si l'index existe
     *
     *  - le javascript ajoute un message qui signale simplement si l'index
     *    existe ou non. Lorsqu'on installe docalist search, ça fait office de
     *    warning (attention, vous allez mettre vos données dans un index qui
     *    existe déjà). Après en routine, c'est une simple confirmation (ok,
     *    l'index que j'ai choisit existe toujours).
     */

    /**
     * Retourne la liste des types de contenus susceptibles d'être indexés.
     *
     * @return array Un tableau de la forme type => libellé du type, trié par
     * libellés.
     */
    protected function availableTypes() {
        // Récupère la liste de tous les types indexables
        $types = apply_filters('docalist_search_get_types', array());

        // Trie par label
        natcasesort($types);

        return $types;
    }
}