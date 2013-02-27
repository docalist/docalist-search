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

/**
 * Classe de base contenant les options de configuration d'un plugin.
 */
abstract class AbstractSettings extends Registrable {
    /**
     * @var array Valeurs par défaut des options de configuration.
     */
    protected $defaults;

    /**
     * @var array Valeurs actuelles des options de configuration.
     */
    protected $options;

    /**
     * Retourne la clé utilisée pour stocker les options du plugin dans la
     * table wp_options de wordpress.
     *
     * Par défaut, il s'agit du nom du plugin mais les classes descendantes
     * peuvent surcharger la propriété $id pour retourner une clé différente.
     *
     * @return string
     */
    public function id() {
        return isset($this->id) ? $this->id : $this->parent->name();
    }

    /**
     * @inheritdoc
     */
    public function register() {
        $this->options = $this->merge(get_option($this->id()), $this->defaults);
    }

    /**
     * Fusionne la configuration aves les valeurs par défaut.
     *
     * Seules les options qui existent dans $default sont conservées (les
     * options inexistantes sont ignorées).
     *
     * @param array $config Les options de configuration.
     * @param array $default Les valeurs par défaut des options.
     *
     * @return array
     */
    protected function merge($config, array $default) {
        if (empty($config)) {
            return $default;
        }

        foreach ($default as $key => &$value) {
            if (is_array($value) && isset($config[$key]) && is_array($config[$key])) {
                $value = $this->merge($config[$key], $value);
            } else {
                isset($config[$key]) && $value = $config[$key];
            }
        }

        return $default;
    }

    /**
     * Retourne la valeur actuelle d'une option de configuration.
     *
     * Si aucun paramètre n'est fourni, la méthode retourne un tableau
     * contenant toutes les options de configuration.
     *
     * @param string|null le nom de l'option à retourner.
     * @param mixed $default la valeur à retourner si l'option n'existe pas.
     *
     * @return mixed
     */
    public function get($option = null, $default = null) {
        if (is_null($option)) {
            return $this->options;
        }

        $options = $this->options;
        foreach (explode('.', $option) as $option) {
            if (!array_key_exists($option, (array)$options)) {
                return $default;
            }

            $options = $options[$option];
        }

        return $options;
    }

}
