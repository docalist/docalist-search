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
 * @version     SVN: $Id: AbstractSettings.php 445 2013-02-27 14:50:48Z
 * daniel.menard.35@gmail.com $
 */

namespace Docalist;

/**
 * Classe de base permettant de gérer les options de configuration d'un plugin.
 */
abstract class AbstractSettings extends Registrable {
    /**
     * @var array Valeurs par défaut des options de configuration.
     *
     * Cette propriété doit être surchargée par les classes descendantes,
     * soit en redéfinissant la propriété (protected $defaults=array(...)),
     * soit dans le constructeur pour des initialisations plus complexes
     * (par exemple si vous avez besoin d'appeller des fonctions comme __()).
     */
    protected $defaults;

    /**
     * @var array Valeurs actuelles des options de configuration.
     */
    protected $settings;

    /**
     * @inheritdoc
     */
    public function register() {
        // Vérifie qu'on a des options
        if (!isset($this->defaults)) {
            $msg = __("La propriété %s de l'objet %s doit être initialisée", 'docalist-core');
            throw new Exception(sprintf($msg, 'defaults', $this->name()));
        }

        // TODO: en mode debug, vérifier qu'aucune clé ne contient '.'

        // Récupère les options stockées dans la base
        $settings = get_option($this->id());

        // Ignore false (clé inexistante), les scalaires, les tableaux vides
        if (!is_array($settings) || empty($settings)) {
            $this->settings = $this->defaults;
        }

        // Fusionne les options de la base avec les options par défaut
        else {
            $this->settings = $this->merge($settings, $this->defaults);
        }
    }

    /**
     * Retourne la configuration par défaut.
     *
     * @return array
     */
    public function defaults() {
        return $this->defaults;
    }

    /**
     * @inheritdoc
     */
    public function settings() {
        return $this->settings;
    }

    /**
     * @inheritdoc
     */
    public function setting($setting) {
        $settings = $this->settings;
        foreach (explode('.', $setting) as $setting) {
            if (!array_key_exists($setting, (array)$settings)) {
                return null;
            }
            $settings = $settings[$setting];
        }

        return $settings;
    }

    /**
     * Fusionne les options de configuration stockées dans la base avec
     * les valeurs par défaut.
     *
     * Seules les options qui existent dans $defaults sont conservées
     * (les options inexistantes sont ignorées).
     *
     * @param array $settings Les options de configuration.
     * @param array $defaults Les valeurs par défaut des options.
     *
     * @return array
     */
    protected function merge(array $settings, array $defaults) {
        if (empty($settings)) {
            return $defaults;
        }

        foreach ($defaults as $key => &$value) {
            if (is_array($value) && isset($settings[$key]) && is_array($settings[$key])) {
                // liste de valeurs
                if (empty($value) || is_int(key($value))) {
                    $value = array_values($settings[$key]);
                }

                // liste de clés
                else {
                    $value = $this->merge($settings[$key], $value);
                }
            } else {
                isset($settings[$key]) && $value = $settings[$key];
            }
        }

        return $defaults;
    }
}
