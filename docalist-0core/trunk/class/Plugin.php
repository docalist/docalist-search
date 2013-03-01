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
abstract class Plugin extends Registrable implements Container {
    // TraitContainer : remplacer implements par use

    /**
     * @var string Chemin absolu du répertoire de base du plugin, sans slash
     * final.
     */
    protected $directory;

    /**
     * @var array Liste des objets déclarés par ce plugin.
     */
    protected $items = array(); // TraitContainer : à enlever


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
     * Interface Container.
     *
     * @inheritdoc
     */
    public function get($name) {
        // TraitContainer : supprimer cette méthode
        return Utils::containerGet($this, $this->items, $name);
    }

    /**
     * Interface Container.
     *
     * @inheritdoc
     */
    public function add(Registrable $object) {
        // TraitContainer : supprimer cette méthode
        return Utils::containerAdd($this, $this->items, $object);
    }

    /**
     * Retourne la valeur d'une option de configuration.
     *
     * Si aucun paramètre n'est fourni, la méthode retourne un tableau
     * contenant toutes les options.
     *
     * @param string|null le nom de l'option à retourner.
     * @param mixed $default la valeur à retourner si l'option n'existe pas.
     *
     * @return mixed
     */
    public function setting($option = null, $default = null) {
        return $this->get('settings')->get($option, $default);
    }

    /**
     * Retourne la liste des outils disponibles pour ce plugin.
     *
     * @return array un tableau d'objets de type
     * {@link Tool}.
     */
    public function tools() {
        return array();
    }

}
