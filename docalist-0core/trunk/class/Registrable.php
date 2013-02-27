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
 * Classe de base pour un objet nommé qui peut être ajouté dans un
 * container (post type, taxonomie, settings, admin page, etc.)
 */
abstract class Registrable {
    /**
     * @var Container L'objet container auquel est rattaché cet objet.
     */
    protected $parent;

    /**
     * @var string L'identifiant unique de cet objet.
     *
     * Cette propriété doit être initialisée par les classes descendantes.
     */
    protected $id;

    /**
     * Retourne ou modifie le container de cet objet.
     *
     * @param Container $container Le container de l'objet.
     *
     * @throws Exception Si l'objet a déjà un container.
     *
     * @return Container|$this
     */
    public function parent(Container $parent = null) {
        // Setter
        if ($parent) {
            if (! is_null($this->parent)) {
                $msg = __("L'objet %s a déjà un container parent", 'docalist-core');
                throw new Exception(sprintf($msg, $this->name()));
            }

            $this->parent = $parent;

            return $this;
        }

        // Getter
        return $this->parent;
    }

    /**
     * Retourne l'identifiant unique de cet objet.
     *
     * Pour une taxonomie ou un post-type, cela correspond au code utilisé en
     * interne par WordPress. Pour des options de configuration, cela
     * correspond à la clé utilisée dans la table wp_options. Etc.
     *
     * @return string
     */
    public function id() {
        return $this->id;
    }

    /**
     * Retourne le nom de l'objet.
     *
     * Par convention, le nom de l'objet correspond à la version en
     * minuscules du dernier élément du nom de classe de l'objet.
     *
     * @return string
     */
    public final function name() {
        return strtolower(Utils::classname($this));
    }


    /**
     * Initialise et enregistre l'objet dans WordPress.
     */
    abstract public function register();

    /**
     * Retourne la valeur d'une option de configuration du plugin.
     *
     * Si aucun paramètre n'est fourni, la méthode retourne un tableau
     * contenant toutes les options.
     *
     * @param string|null Le nom de l'option à retourner.
     * @param mixed $default La valeur à retourner si l'option n'existe pas
     * (null par défaut).
     *
     * @return mixed
     */
    public function setting($option = null, $default = null) {
        static $settings;

        is_null($settings) && $settings = $this->parent->get('settings');

        return $settings->get($option, $default);
    }

}
