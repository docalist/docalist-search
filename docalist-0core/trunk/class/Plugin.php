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
use Exception;

/**
 * Classe de base abstraite représentant un plugin Docalist.
 */
abstract class Plugin extends Registrable implements ContainerInterface {
    use ContainerTrait;

    /**
     * Nom du transient utilisé pour stocker les admin notices.
     */
    const ADMIN_NOTICE_TRANSIENT = 'dcl_admin_notices';

    /**
     * @var string Chemin absolu du répertoire de base du plugin, sans slash
     * final.
     */
    protected $directory;

    /**
     * Initialise le plugin.
     * - Charge le domaine de texte du plugin pour gérer les traductions
     *
     * @param string $id L'identifiant du plugin (le nom du fichier principal
     * du plugin).
     *
     * @param string $directory Chemin complet du répertoire de base du plugin.
     */
    public function __construct($id, $directory) {
        // Stocke le nom et le répertoire de base de ce plugin
        $this->id = $id;
        $this->directory = $directory;

        // Charge les fichiers de traduction du plugin
        if ($domain = $this->textDomain()) {
            load_plugin_textdomain($domain, false, $directory . '/languages');
        }
    }

    /**
     * Retourne le répertoire de base du plugin.
     *
     * @param boolean $relative Indique s'il faut retourner le chemin absolu
     * (valeur par défaut) ou bien un chemin relatif au répertoire plugins de
     * Wordpress.
     *
     * @return string Le path répertoire dans lequel est installé le plugin.
     * Retourne un chemin absolu si $relative est à faux et un chemin relatif
     * au répertoire "wp-content/plugins" de Wordpress sinon.
     */
    public function directory($relative = false) {
        return $relative ? basename($this->directory) : $this->directory;
    }

    /**
     * Retourne le domaine de texte à utiliser pour charger les fichiers
     * de traduction du plugin.
     *
     * Par convention, le domaine du plugin correspond à son identifiant mais
     * les classes descendantes peuvent surcharger cette méthode pou retourner
     * un domaine différent.
     *
     * Si votre plugin n'a aucune chaine à traduire, vous pouvez retourner
     * false. Dans ce cas, aucun fichier de traduction ne sera chargé.
     *
     * @return string|false
     */
    public function textDomain() {
        return $this->id();
    }

    /**
     * Registrable.
     *
     * @inheritdoc
     */
    public function plugin() {
        return $this;
    }

    /**
     * Registrable.
     *
     * @inheritdoc
     */
    public function setting($setting) {
        return $this->get('settings')->setting($setting);
    }

    /**
     * Registrable.
     *
     * @inheritdoc
     */
    public function settings() {
        return $this->get('settings')->settings();
    }

    /**
     * Affiche un message (admin notice) à l'utilisateur.
     *
     * @param string $message le message à afficher.
     * @param bool $isError true=affiche un message d'erreur , false (par
     * défaut), affiche un simple message d'information.
     */
    public function adminNotice($message, $isError = false) {
        if (false === $notices = get_transient(self::ADMIN_NOTICE_TRANSIENT)) {
            $notices = array();
        }

        $notices[] = array(
            $message,
            $isError
        );
        set_transient(self::ADMIN_NOTICE_TRANSIENT, $notices, 3600 * 24 * 10);
        // 10 jours

        // remarque : c'est le plugin docalist_core qui se charge d'afficher
        // les notices via un add_action('admin_notices', ...)
    }

}
