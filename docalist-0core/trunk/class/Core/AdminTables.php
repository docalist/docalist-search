<?php
/**
 * This file is part of the 'Docalist Core' plugin.
 *
 * Copyright (C) 2012, 2013 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Tables
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     $Id$
 */
namespace Docalist\Core;

use Docalist\AdminPage;
use Docalist\Table\TableManager;
use Exception;

/**
 * Gestion des tables d'autorité
 */
class AdminTables extends AdminPage {
    /**
     * {@inheritdoc}
     */
    protected $defaultAction = 'TablesList';

    protected $capability = [
        'default' => 'manage_options',
    ];

    /**
     *
     * @param Settings $settings
     */
    public function __construct() {
        // @formatter:off
        parent::__construct(
            'docalist-tables',                          // ID
            'options-general.php',                      // page parent
            __("Tables d'autorité", 'docalist-core')  // libellé menu
        );
        // @formatter:on
    }

    /**
     * Retourne le gestionnaire de tables.
     *
     * @return TableManager
     */
    protected function tableManager() {
        return apply_filters('docalist_get_table_manager', null);
    }

    /**
     * Retourne l'objet TableInfo d'une table.
     *
     * @param string $tableName
     *
     * @return false|TableInfo
     */
    protected function tableInfo($tableName) {
        if ($info = $this->tableManager()->info($tableName)) {
            return $info;
        }

        $title = __('Table non trouvée', 'docalist-core');
        $msg = __('La table "%s" n\'existe pas.', 'docalist-core');
        wp_die(sprintf($msg, $tableName), $title);
    }

    /**
     * Liste des tables d'autorité.
     */
    public function actionTablesList() {
        return $this->view('docalist-core:tables/tables-list', [
            'tables' => $this->tableManager()->info(),
        ]);
    }

    /**
     * Modifie le contenu d'une table d'autorité.
     */
    public function actionTableEdit($tableName) {
        // Vérifie que la table à modifier existe
        $tableInfo = $this->tableInfo($tableName);

        // Ouvre la table
        $table = $this->tableManager()->get($tableName);

        // Gère la sauvegarde
        if ($this->isPost()) {
//             echo 'POST<br />';
//             echo '<pre>';
//             var_export($_POST);
//             echo '</pre>';
//             die();
//             $this->tableManager()->update(null, null, $data);

            return $this->json([
                'success' => true,
                'url' => $this->url('TablesList')
            ]);
        }

        // Récupère la liste des champs
        $fields = $table->fields();

        // Récupère les données de la table
        // On veut un tableau d'objets, pas un tableau associatif
        $data = $table->search('ROWID,' . implode(',', $fields));
        $data = array_values($data);

        // Affiche l'éditeur
        return $this->view('docalist-core:tables/table-edit', [
            'tableName' => $tableName,
            'tableInfo' => $tableInfo,
            'fields' => $fields,
            'data' => $data,
            'readonly' => ! $tableInfo->user
        ]);
    }

    /**
     * Copie une table.
     */
    public function actionTableCopy($tableName) {
        $tableInfo = $this->tableInfo($tableName);

        // Requête post : copie la table
        $error = '';
        if ($this->isPost()) {
            $_POST = wp_unslash($_POST);

            $name = $_POST['name'];
            $label = $_POST['label'];
            $nodata = (bool) $_POST['nodata'];

            try {
                $this->tableManager()->copy($tableName, $name, $label, $nodata);

                return $this->redirect($this->url('TablesList'), 303);
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }

        // Suggère un nouveau nom pour la table
        for($i=2; ; $i++) {
            $name = "$tableName-$i";
            if (is_null($this->tableManager()->info($name))) {
                break;
            }
        }
        $tableInfo->name = $name;
        $tableInfo->label = sprintf(__('Copie de %s', 'docalist-core'), $tableInfo->label);

        return $this->view('docalist-core:tables/table-copy', [
            'tableName' => $tableName,
            'tableInfo' => $tableInfo,
            'error' => $error
        ]);
    }

    /**
     * Modifie les propriétés d'une table d'autorité.
     */
    public function actionTableProperties($tableName) {
        $tableInfo = $this->tableInfo($tableName);

        $error = '';
        if ($this->isPost()) {
            $_POST = wp_unslash($_POST);

            $name = $_POST['name'];
            $label = $_POST['label'];

            try {
                $this->tableManager()->update($tableName, $name, $label);

                return $this->redirect($this->url('TablesList'), 303);
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            $tableInfo->name = $name;
            $tableInfo->label = $label;
        }

        return $this->view('docalist-core:tables/table-properties', [
            'tableName' => $tableName,
            'tableInfo' => $tableInfo,
            'error' => $error
        ]);
    }

    /**
     * Supprime une table d'autorité.
     */
    public function actionTableDelete($tableName, $confirm = false) {
        $tableInfo = $this->tableInfo($tableName);

        // Demande confirmation
        if (! $confirm) {
            $msg  = __('La table "%s" va être supprimée. ', 'docalist-core');
            $msg .= __('Cette action ne peut pas être annulée.', 'docalist-core');
            $msg .= '<br />';
            $msg .= __('Assurez-vous que cette table n\'est plus utilisée. ', 'docalist-core');
            $msg .= __('Si vous avez un doute, vous pouvez <a href="%s">renommer la table</a> et vérifier que tout fonctionne avant de la supprimer.', 'docalist-core');

            $href = $this->url('TableProperties', $tableName);
            $msg = sprintf($msg, $tableName, $href);

            return $this->confirm($msg, __('Supprimer une table', 'docalist-core'));
        }

        // Essaie de supprimer la table
        try {
            $this->tableManager()->delete($tableName);

            return $this->redirect($this->url('TablesList'), 303);
        } catch (Exception $e) {
            return $this->view('docalist-core:error', [
                'h2' => __('Supprimer une table', 'docalist-core'),
                'h3' => __('Erreur', 'docalist-core'),
                'message' => $e->getMessage(),
                'back' => $this->url('TablesList'),
            ]);
        }
    }
}