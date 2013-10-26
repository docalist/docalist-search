<?php
/**
 * This file is part of the "Docalist Core" plugin.
 *
 * Copyright (C) 2012, 2013 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */

namespace Docalist\Core;

use Docalist\AbstractPlugin;
use Docalist\Cache\FileCache;
use Docalist\Table\TableManager;
use Docalist\Table\TableInfo;

/**
 * Plugin core de Docalist.
 */
class Plugin extends AbstractPlugin {

    /**
     * La configuration du plugin.
     *
     * @var Settings
     */
    protected $settings;

    /**
     * Le cache de fichier de Docalist.
     *
     * Initialisé lors du premier appel à {@link fileCache()}.
     *
     * @var FileCache
     */
    protected $fileCache;

    /**
     * Le gestionnaire de tables d'autorité de Docalist.
     *
     * Initialisé lors du premier appel à {@link tableManager()}.
     *
     * @var TableManager
     */
    protected $tableManager;

    /**
     * {@inheritdoc}
     */
    public function register() {
        // Charge la configuration du plugin
        $this->settings = new Settings('docalist-core');

        // Crée le filtre docalist_get_file_cache
        add_filter('docalist_get_file_cache', array($this, 'fileCache'));

        // Crée le filtre docalist_get_table_manager
        add_filter('docalist_get_table_manager', array($this, 'tableManager'));

        // Crée le filtre docalist_get_table
        add_filter('docalist_get_table', function($table) {
            return $this->tableManager()->get($table);
        });

        // Enregistre nos propres tables quand c'est nécessaire
        add_action('docalist_register_tables', array($this, 'registerTables'));

        // Back office
        add_action('admin_menu', function () {

            // Page "Gestion des tables d'autorité"
            new AdminTables();
        });

        // Gestion des admin notices - à revoir, pas içi
        add_action('admin_notices', function(){
            $this->showAdminNotices();
        });
    }

    /**
     * Retourne le cache de fichiers de Docalist.
     *
     * L'instance est initialisée lors du premier appel.
     *
     * @return FileCache
     */
    public function fileCache() {
        if (is_null($this->fileCache)) {
            // Fait chier wordpress !
            // Impossible de récupérer le home directory de façon fiable.
            // ABSPATH : ne marche pas si wp dans un sous répertoire
            // get_home_path(), get_real_file_to_edit() : uniquement en admin
            // utiliser directement WP_PLUGIN_DIR  est déconseillé
            // plugin_dir_path() ne fait pas ce qu'on croit
            // etc...
            $root = dirname(dirname(dirname(dirname(__DIR__)))); // répertoire parent de /plugins
            $dir = get_temp_dir() . 'docalist-cache';
            $this->fileCache = new FileCache($root, $dir);
        }

        return $this->fileCache;
    }

    /**
     * Retourne le gestionnaire de table de Docalist.
     *
     * L'instance est initialisée lors du premier appel.
     *
     * L'action 'docalist_register_tables' est déclenchée pour permettre aux
     * plugins d'enregistrer leurs tables prédéfinies.
     *
     * @return TableManager
     */
    public function tableManager() {
        if (is_null($this->tableManager)) {
            $this->tableManager = new TableManager($this->settings);
        }

        return $this->tableManager;
    }

    /**
     * Affiche les admin-notices qui ont été enregistrés
     * (cf AbstractPlugin::adminNotice).
     */
    protected function showAdminNotices() {
        // Adapté de : http://www.dimgoto.com/non-classe/wordpress-admin_notice/
        if (false === $notices = get_transient(self::ADMIN_NOTICE_TRANSIENT)) {
            return;
        }

        foreach($notices as $notice) {
            list($message, $isError) = $notice;
            printf(
                '<div class="%s"><p>%s</p></div>',
                $isError ? 'error' : 'updated',
                $message
            );
        }

        delete_transient(self::ADMIN_NOTICE_TRANSIENT);
    }

    /**
     * Enregistre les tables prédéfinies.
     *
     * @param TableManager $tableManager
     */
    public function registerTables(TableManager $tableManager) {
        $dir = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'tables'  . DIRECTORY_SEPARATOR;

        $tableManager->register(new TableInfo([
            'name' => 'countries',
            'path' => $dir . 'countries.php',
            'label' => __('Table des pays', 'docalist-core'),
            'user' => false,
        ]));

        $tableManager->register(new TableInfo([
            'name' => 'languages',
            'path' => $dir . 'languages.php',
            'label' => __('Table des langues', 'docalist-core'),
            'user' => false,
        ]));
    }
}