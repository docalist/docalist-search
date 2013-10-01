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

namespace Docalist;
use Docalist\Tools\ToolsList;
use Docalist\Cache\FileCache;
use Docalist\Table\Manager;

/**
 * Plugin core de Docalist.
 */
class Core extends AbstractPlugin {

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
     * @var Manager
     */
    protected $tableManager;

    /**
     * {@inheritdoc}
     */
    public function register() {
        // Crée le filtre docalist_get_file_cache
        add_filter('docalist_get_file_cache', array($this, 'fileCache'));

        // Crée le filtre docalist_get_table
        add_filter('docalist_get_table', function($table) {
            return $this->tableManager()->get($table);
        });

        // Enregistrre nos propres tables quand c'est nécessaire
        add_action('docalist_register_tables', function(Manager $manager) {
            $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tables'  . DIRECTORY_SEPARATOR;
            $manager->register(array(
                'countries' => $dir . 'countries.php',
                'languages' => $dir . 'languages.php',
            ));
        }, 10, 2);

        // Utile ?
        // add_filter('docalist_get_table_manager', array($this, 'tableManager'));

        // Utile ?
        // add_filter('docalist_get_table_path', function($table){
        //     return $this->tableManager()->path($table);
        // });

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
        // Initialise le cache au premier appel
        if (is_null($this->fileCache)) {
            // Fait chier wordpress !
            // Impossible de récupérer home directory
            // ABSPATH : ne marche pas si wp dans un sous répertoire
            // get_home_path(), get_real_file_to_edit() : uniquement en admin
            // utiliser directement WP_PLUGIN_DIR  est déconseillé
            // plugin_dir_path() ne fait pas de qu'on croit
            // etc...
            $root = dirname(dirname(__DIR__)); // répertoire /plugins
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
     * plugins d'enregistrer leurs tables.
     *
     * @return Manager
     */
    public function tableManager() {
        // Au premier appel, initialise le manager
        if (is_null($this->tableManager)) {

            // Crée l'instance
            $this->tableManager = new Manager();

            // Demande à tout le monde de déclarer ses tables
            do_action('docalist_register_tables', $this->tableManager);
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
}