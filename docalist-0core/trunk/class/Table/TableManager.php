<?php
/**
 * This file is part of a "Docalist Core" plugin.
 *
 * Copyright (C) 2012, 2013 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package Docalist
 * @subpackage Table
 * @author Daniel Ménard <daniel.menard@laposte.net>
 * @version SVN: $Id$
 */
namespace Docalist\Table;

use Docalist\Core\Settings;
use Docalist\Cache\FileCache;
use Exception;

/**
 * Gestionnaire de tables d'autorité.
 *
 */
class TableManager {
    /**
     * Les settings de Docalist Core.
     *
     * On n'utilise que l'entrée "tables".
     *
     * @var Settings
     */
    protected $settings;

    /**
     * Liste des tables déclarées.
     *
     * @var TableInfo[] Un tableau d'objets TableInfo indexé par nom.
     */
    protected $tables;

    /**
     * Liste des tables ouvertes.
     *
     * @var TableInterface[] Un tableau d'objets TableInterface indexé par nom.
     */
    protected $opened;

    /**
     * Initialise le gestionnaire de tables.
     *
     * @param Settings $settings Les settings de Docalist Core dans lesquels
     * sont stockées les tables personnalisées.
     */
    public function __construct(Settings $settings) {
        $this->settings = $settings;
        $this->registerTables();
    }

    /**
     * Enregistre toutes les tables existantes.
     *
     * Les tables personnalisées (stockées dans les settings) sont enregistrées
     * en premier. On appelle ensuite le hook 'docalist_register_tables' qui
     * permet à tous les plugins d'enregistrer leurs tables prédéfinies.
     */
    protected function registerTables() {
        $this->tables = $this->opened = [];

        // Enregistre toutes les tables personnalisées
        foreach($this->settings->tables as $tableInfo) {
            $this->register($tableInfo);
        }

        // Enregistre toutes les tables prédéfinies (par les plugins)
        do_action('docalist_register_tables', $this);
    }

    /**
     * Déclare une table d'autorité.
     *
     * @param string $name Nom (unique) de la table.
     * @param string $path Path absolu de la table.
     * @param string $label Libellé/description de la table ($name si vide).
     * @param string $type Type de la table ($name si non fourni).
     * @param bool $user Indique s'il s'agit d'une table utilisateur ou d'une
     * table prédéfinie.
     *
     * @throws Exception Si la table est déjà déclarée.
     */
    public function register(TableInfo $tableInfo) {
        $name = $tableInfo->name;

        if (isset($this->tables[$name])) {
            $msg = __('La table "%s" existe déjà', 'docalist-core');
            throw new Exception(sprintf($msg, $name));
        }
        empty($tableInfo->type) && $tableInfo->type = $name;
        $this->tables[$name] = $tableInfo;
    }

    /**
     * Retourne des informations sur les tables déclarées.
     *
     * @param string $name Le nom de la table recherchée ou null pour obtenir
     * la liste de toutes les tables.
     *
     * @return null|TableInfo|TableInfo[] Retourne null si la table demandée
     * n'existe pas, un objet TableInfo si un nom de table a été indiqué et un
     * tableau d'objets TableInfo si aucun paramètre n'a été fourni.
     */
    public function info($name = null) {
        if (is_null($name)) {
            return $this->tables;
        }

        return isset($this->tables[$name]) ? $this->tables[$name] : null;

        // @todo: permettre de filtrer sur type et sur user
    }

    /**
     * Ouvre une table.
     *
     * @param string $name Nom de la table à retourner.
     *
     * @return TableInterface
     *
     * @throws Exception Si la table indiquée n'a pas été enregistrée.
     */
    public function get($name) {
        // Si la table est déjà ouverte, retourne l'instance en cours
        if (isset($this->opened[$name])) {
            return $this->opened[$name];
        }

        // Vérifie que la table demandée a été enregistrée
        if (! isset($this->tables[$name])) {
            $msg = __('La table "%s" n\'existe pas', 'docalist-core');
            throw new Exception(sprintf($msg, $name));
        }

        // Ouvre la table
        $path = $this->tables[$name]->path;
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        switch($extension) {
            case 'sqlite':
            case 'db':
                return $this->opened[$name] = new SQLite($path);

            case 'csv':
            case 'txt':
                return $this->opened[$name] = new CsvTable($path);

            case 'php':
                return $this->opened[$name] = new PhpTable($path);

            default:
                $msg = __('Table "%s" : type non géré "%s"', 'docalist-core');
                throw new Exception(sprintf($msg, $name, $extension));
        }
    }

    /**
     * Retourne le répertoire dans lequel sont stockées les tables utilisateur.
     *
     * @return string
     */
    protected function tableDirectory() {
        $directory = wp_upload_dir();
        $directory = $directory['basedir'];
        $directory .= DIRECTORY_SEPARATOR . 'tables';

        if (! is_dir($directory) && ! @mkdir($directory, 0770, true))
        {
            $msg = __('Impossible de créer le répertoire "%s" pour les tables', 'docalist-core');
            throw new Exception(sprintf($msg, $directory));
        }

        return $directory;
    }

    /**
     * Vérifie que le nom de table passé en paramètre est correct et qu'il
     * n'existe pas déjà une table portant ce nom.
     *
     * @param string $name Nom de table à vérifier.
     *
     * @throws Exception
     */
    protected function checkName($name) {
        // Vérifie que le nom est correct
        if (! preg_match('~^[a-zA-Z0-9_-]+$~', $name)) {
            $msg = __('Nom de table incorrect "%s" (caractères autorisés "a..z 0..9 - _", longueur minimale : 1).', 'docalist-core');
            throw new Exception(sprintf($msg, $name));
        }

        // Vérifie que le nom n'existe pas déjà
        if (isset($this->tables[$name])) {
            $msg = __('Il existe déjà une table "%s".', 'docalist-core');
            throw new Exception(sprintf($msg, $name));
        }
    }

    /**
     * Crée une nouvelle table personnalisée en recopiant une table existante.
     *
     * @param string $name Nom de la table à recopier.
     * @param string $newName Nom de la table à créer.
     * @param string $label Libellé de la nouvelle table.
     * @param bool $nodata true : recopier uniquement la structure de la table,
     * false : recopier la structure et les données.
     *
     *
     * @throws Exception
     * - si la table $name n'existe pas
     * - si le nom de la nouvelle table n'est pas correct ou n'est pas unique
     * - s'il existe déjà une table $newName
     * - si le répertoire des tables utilisateurs (wp-content/upload/tables)
     *   ne peut pas être créé
     * - si un fichier $newName.txt existe déjà dans ce répertoire
     * - si une erreur survient durant la copie
     */
    public function copy($name, $newName, $label, $nodata) {
        // Vérifie que la table source existe
        if (! isset($this->tables[$name])) {
            $msg = __('La table "%s" n\'existe pas.', 'docalist-core');
            throw new Exception(sprintf($msg, $name));
        }

        // Vérifie que le nouveau nom est correct et unique
        $this->checkName($newName);

        // Détermine le path de la table
        $fileName = $newName . '.txt';
        $path = $this->tableDirectory() . DIRECTORY_SEPARATOR . $fileName;

        // Vérifie qu'il n'existe pas déjà un fichier à ce path
        if (file_exists($path)) {
            $msg = __('Il existe déjà un fichier "%s" dans le répertoire des tables.', 'docalist-core');
            throw new Exception(sprintf($msg, $fileName));
        }

        // Charge la table source
        $source = $this->get($name);
        $fields = $source->fields();
        $data = $source->search('ROWID,' . implode(',', $fields));

        // Génère le fichier CSV de la nouvelle table
        $file = fopen($path, 'w');
        fputcsv($file, $fields, ';', '"');
        if (! $nodata) {
            foreach($data as $entry) {
                fputcsv($file, (array) $entry, ';', '"');
            }
        }
        fclose($file);

        // Crée la structure TableInfo de la nouvelle table
        $table = new TableInfo();
        $table->name = $newName;
        $table->path = $path;
        $table->label = $label;
        $table->type = $this->tables[$name]->type;
        $table->user = true;

        // Enregistre la nouvelle table dans les settings
        $this->settings->tables[] = $table;
        $this->settings->save();

        // On ré-enregistre toutes les tables, au cas où on ait besoin de
        // réafficher la liste complète des tables (exemple : SettingsPage).
        // Pas très efficace, car on ferme toutes les tables ouvertes pour les
        // réouvrir juste après, mais c'est une opération rare
        $this->registerTables();
    }

    /**
     * Met à jour les propriétés et/ou le contenu d'une table personnalisée.
     *
     * @param unknown $name
     * @param string $newName
     * @param string $label
     * @param array $data
     *
     * @throws Exception
     * - si la table $name n'existe pas.
     * - si la table $name n'est pas une table personnalisée.
     * - s'il existe déjà une table $newName.txt dans le répertoire des table.
     * - si la table ne peut pas être renommée
     */
    public function update($name, $newName = null, $label = null, array $data = null) {
        // Vérifie que la table à modifier existe
        if (! isset($this->tables[$name])) {
            $msg = __('La table "%s" n\'existe pas.', 'docalist-core');
            throw new Exception(sprintf($msg, $name));
        }
        $tableInfo = $this->tables[$name];

        // Vérifie qu'il s'agit d'une table personnalisée
        if (! $tableInfo->user) {
            $msg = __('La table "%s" est une table prédéfinie, elle ne peut pas être modifiée.', 'docalist-core');
            throw new Exception(sprintf($msg, $name));
        }

        $path = $tableInfo->path;
        ($newName === $name) && $newName = null;
        ($label === $tableInfo->label) && $label = null;

        // Mise à jour du contenu de la table
        if (! is_null($data)) {
            // Récupère la liste des champs
            $fields = $this->get($name)->fields();

            // Ferme la table
            unset($this->opened[$name]);

            // Génère le fichier CSV de la nouvelle table
            $file = fopen($path, 'w');
            fputcsv($file, $fields, ';', '"');
            foreach($data as $entry) {
                fputcsv($file, (array) $entry, ';', '"');
            }
            fclose($file);
        }

        // Changement de nom
        if (! is_null($newName)) {
            // Vérifie que le nom est correct et unique
            $this->checkName($newName);

            // Détermine le nouveau path
            $p = pathinfo($path);
            $newPath = $p['dirname'] . DIRECTORY_SEPARATOR . $newName . '.' . $p['extension'];

            // Vérifie qu'il n'existe pas déjà un fichier avec ce nom
            if (file_exists($newPath)) {
                $msg = __('Il existe déjà un fichier "%s" dans le répertoire des tables.', 'docalist-core');
                throw new Exception(sprintf($msg, $newName));
            }

            // Renomme le fichier
            if (! @rename($path, $newPath)) {
                $msg = __('Impossible de renommer la table "%s" en "%s".', 'docalist-core');
                throw new Exception(sprintf($msg, $name, $newName));
            }

            // Supprime l'ancienne table du cache
            /* @var $cache FileCache */
            $cache = apply_filters('docalist_get_file_cache', null);
            $cache->has($path) && $cache->clear($path);
        }

        // Met à jour les settings
        if (! is_null($newName) || ! is_null($label)) {
            /* @var $tableInfo TableInfo */
            foreach($this->settings->tables as $tableInfo) {
                if ($tableInfo->name === $name) {
                    if (! is_null($newName)) {
                        $tableInfo->name = $newName;
                        $tableInfo->path = $newPath;
                    }

                    if (! is_null($label)) {
                        $tableInfo->label = $label;
                    }

                    break;
                }
            }

            // Enregistre les settings
            $this->settings->save();

            // Ré-enregistre toutes les tables (cf. copy()).
            $this->registerTables();
        }
    }

    /**
     * Supprime une table personnalisée.
     *
     * @param string $name Nom de la table à supprimer.
     *
     * @throws Exception
     * - si la table $name n'existe pas.
     * - si la table $name n'est pas une table personnalisée.
     * - si la suppression échoue
     */
    public function delete($name) {
        // Vérifie que la table à supprimer existe
        if (! isset($this->tables[$name])) {
            $msg = __('La table "%s" n\'existe pas.', 'docalist-core');
            throw new Exception(sprintf($msg, $name));
        }
        $tableInfo = $this->tables[$name];

        // Vérifie qu'il s'agit d'une table personnalisée
        if (! $tableInfo->user) {
            $msg = __('La table "%s" est une table prédéfinie, elle ne peut pas être supprimée.', 'docalist-core');
            throw new Exception(sprintf($msg, $name));
        }

        $path = $tableInfo->path;

        // Ferme la table
        unset($this->opened[$name]);

        // Supprime le fichier CSV
        if (! @unlink($path)) {
            $msg = __('Impossible de supprimer la table "%s".', 'docalist-core');
            throw new Exception(sprintf($msg, $name));
        }

        // Supprime l'ancienne table du cache
        /* @var $cache FileCache */
        $cache = apply_filters('docalist_get_file_cache', null);
        $cache->has($path) && $cache->clear($path);

        // Met à jour les settings
        /* @var $tableInfo TableInfo */
        foreach($this->settings->tables as $i => $tableInfo) {
            if ($tableInfo->name === $name) {
                unset($this->settings->tables[$i]);
                break;
            }
        }

        // Enregistre les settings
        $this->settings->save();

        // Ré-enregistre toutes les tables (cf. copy()).
        $this->registerTables();
    }
}