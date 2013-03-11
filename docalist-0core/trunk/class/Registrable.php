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
 * @version     SVN: $Id: Registrable.php 444 2013-02-27 14:49:45Z
 * daniel.menard.35@gmail.com $
 */

namespace Docalist;
use Exception;

/**
 * Classe de base pour un objet nommé qui peut être ajouté dans un
 * container (post type, taxonomie, settings, admin page, settings page, etc.)
 */
abstract class Registrable {
    /**
     * @var Container L'objet container auquel est rattaché cet objet.
     */
    protected $parent;

    /**
     * @var string L'identifiant unique de cet objet.
     *
     * La majorité des objets enregistrables sont capables de construire
     * automatiquement leur id en combinant le nom de leur classe avec le
     * nom de leur container (cf. {@link id()}).
     *
     * Dans certains cas, il est nécessaire de définir explicitement un id
     * pour que celui-ci puisse être facilement utilisé de façon transversale
     * (par exemple le nom d'un custom post type ou d'une taxonomie).
     *
     * Dans ce cas, les classes descendantes peuvent redéfinir cette propriété
     * ou l'initialiser dans leur constructeur.
     *
     * La méthode {@link id()} retournera alors l'id indiqué.
     */
    protected $id;

    /**
     * Initialise et enregistre l'objet dans WordPress.
     */
    abstract public function register();

    /**
     * Retourne ou modifie le container de cet objet.
     *
     * Appellée sans paramètre, la méthode retourne le container parent
     * de l'objet ou null si l'objet n'a pas de parent.
     *
     * Utilisée comme Setter, elle ajoute l'objet dans le containeur passé
     * en paramètre.
     *
     * Remarque :
     *
     * On ne peut pas modifier le parent d'un objet Registrable : une fois
     * qu'un objet a été ajouté à un container (il a un parent), il ne peut
     * plus être ajouté à un autre objet. Une exception sera levée si on
     * essaie de modifier le parent d'un objet qui a déjà un parent.
     *
     * @param Container $container Le container de l'objet.
     *
     * @throws Exception Si l'objet a déjà un container.
     *
     * @return Container|$this
     */
    public function parent(Container $parent = null) {
        // Getter
        if (is_null($parent)) {
            return $this->parent;
        }

        // Setter
        if (!is_null($this->parent)) {
            $msg = __("L'objet %s a déjà un parent", 'docalist-core');
            throw new Exception(sprintf($msg, $this->name()));
        }

        $this->parent = $parent;

        return $this;
    }

    /**
     * Retourne le plugin auquel est rattaché cet objet.
     *
     * La méthode se contente d'appeller la méthode plugin() de son container.
     *
     * @throws Exception Si l'objet n'a pas de parent.
     *
     * @return AbstractPlugin
     */
    public function plugin() {
        if (!$this->parent) {
            $msg = __("L'objet %s n'a pas de parent", 'docalist-core');
            throw new Exception(sprintf($msg, $this->name()));
        }

        return $this->parent->plugin();
    }

    /**
     * Retourne l'identifiant unique de cet objet.
     *
     * Pour une taxonomie ou un post-type, cela correspond au code utilisé en
     * interne par WordPress. Pour des options de configuration, cela
     * correspond à la clé utilisée dans la table wp_options, etc.
     *
     * @return string
     */
    public function id() {
        // Si la classe descendante a explicitement définit un id, on le prend
        if (isset($this->id)) {
            return $this->id;
        }

        // Sinon, on le construit (et on le stocke pour la prochaine fois)
        $this->id = $this->name();
        if ($this->parent) {
            $this->id = $this->parent->id() . '-' . $this->id;
        }

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
    public function name() {
        return strtolower(Utils::classname($this));
    }

    /**
     * Retourne la configuration actuelle du plugin.
     *
     * Tous les objets Registrable ont accès à la configuration du plugin
     * auquel ils sont ratttachés.
     *
     * A tout niveau (post type, metabox, settingspage, etc.) on peut ainsi
     * appeller $this->setting(option) ou $this->settings().
     *
     * @return array
     */
    public function settings() {
        return $this->plugin()->settings();
    }

    /**
     * Retourne la valeur actuelle d'une option de configuration.
     *
     * Vous pouvez utiliser des points dans les noms d'options pour
     * accéder directement à une sous-option.
     *
     * Par exemple :
     * <code>$this->setting('options.display.contrast')</code>
     *
     * est équivalent à
     *
     * <code>
     * $settings = $this->settings();
     * $settings['options']['display']['constrast']
     * </code>
     *
     * mais ne générera aucune erreur si l'un des noms indiqués n'existe pas.
     *
     * @param string le nom de l'option à retourner.
     *
     * @return array|scalar|null Retourne un tableau si $setting désigne un
     * groupe d'options, un scalaire si $options est une option et null si la
     * clé demandée n'existe pas.
     */
    public function setting($setting) {
        return $this->plugin()->setting($setting);
    }

}
