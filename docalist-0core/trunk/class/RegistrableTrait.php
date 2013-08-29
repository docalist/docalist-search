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
 * Implémentation standard de l'interface {@link RegistrableInterface}.
 */
trait RegistrableTrait {
    /**
     * @var ContainerInterface L'objet container auquel est rattaché cet objet.
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
     * @var string Le nom du hook WordPress qui doit être utilisé pour
     * créer des objets de ce type.
     */
    protected $hookName = 'init';

    /**
     * Initialise et enregistre l'objet dans WordPress.
     */
    public function register() {

    }

    /**
     * Retourne ou modifie le container de cet objet.
     *
     * Appellée sans paramètre, la méthode retourne le container parent de
     * l'objet ou null si l'objet n'a pas de parent.
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
     * @param ContainerInterface $container Le container de l'objet.
     *
     * @throws Exception Si l'objet a déjà un container.
     *
     * @return ContainerInterface|$this Retourne le container parent de l'objet
     * ou $this si la méthode est utilisée comme Setter.
     */
    public function parent(ContainerInterface $parent = null) {
        // Getter
        if (is_null($parent)) {
            return $this->parent;
        }

        // Setter. Le parent d'un objet ne peut pas être changé
        if (!is_null($this->parent)) {
            $msg = __("L'objet %s a déjà un parent", 'docalist-core');
            throw new Exception(sprintf($msg, $this->id()));
        }

        // Stocke le parent
        $this->parent = $parent;
        if (! WP_DEBUG) {
            return $this;
        }

        // En mode debug, vérifie que l'objet a été créé au bon moment
/*
        $hook = $this->hookName();
        $current = current_filter();
        if ($current !== $hook) {
            $title = sprintf('Erreur dans le plugin %s', $this->plugin()->id());
            $msg = '
                <h1>%s</h1>
                <p>L\'objet <code>%s</code> est instancié trop tôt ou trop
                tard (pendant l\'action <code>%s</code>).</p>
                <p>Vous devez encapsuler la création de votre objet dans un
                appel à <a href="%s"><code>add_action()</code></a> et
                utiliser le hook <code>%s</code>.</p>

                <p>Par exemple :</p>

                <pre>
                add_action(\'%s\', function() {$this->add(new %s)});
                </pre>';
            $msg = sprintf($msg,
                $title,
                get_class($this),
                $current,
                'http://codex.wordpress.org/Function_Reference/add_action',
                $hook,
                $hook,
                Utils::classname($this)
            );

            wp_die($msg, $title);
        }
*/
        return $this;
    }

    /**
     * Retourne le plugin auquel est rattaché cet objet.
     *
     * La méthode se contente d'appeller la méthode plugin() de son container.
     *
     * @throws Exception Si l'objet n'a pas de parent ou s'il n'est pas rattaché
     * à un plugin.
     *
     * @return AbstractPlugin
     */
    public function plugin() {
        if (!$this->parent) {
            $msg = __("L'objet %s n'a pas de parent", 'docalist-core');
            throw new Exception(sprintf($msg, $this->id()));
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
     * Si un id a été explicitement définit (dans {@link $id}), c'est celui-ci
     * qui est retourné. Sinon, la méthode construit un id en prenant l'ID du
     * parent et le nom de l'objet
     *
     * @return string
     */
    public function id() {
        // Si la classe descendante a explicitement définit un id, on le prend
        if (isset($this->id)) {
            return $this->id;
        }

        // Sinon, on le construit (et on le stocke pour la prochaine fois)
        $this->id = strtolower(Utils::classname($this));
        if ($this->parent) {
            $this->id = $this->parent->id() . '-' . $this->id;
        }

        return $this->id;
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
     * Tous les objets Registrable ont accès à la configuration du plugin
     * auquel ils sont ratttachés.
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
     * @param string $setting le nom de l'option de configuration à retourner.
     *
     * @return array|scalar|null Retourne un tableau si $setting désigne un
     * groupe d'options, un scalaire si $setting est une option et null si la
     * clé demandée n'existe pas.
     */
    public function setting($setting) {
        return $this->plugin()->setting($setting);
    }

    /**
     * Retourne le nom du hook qui doit être utilisé pour créer des objets
     * de ce type.
     *
     * Cette méthode sert à vérifier que les objets sont créés "au bon moment"
     * (par exemple ne pas créer des pages d'administration quand on est coté
     * front-office). Elle n'est appellée qu'en mode debug.
     *
     * Lorsque l'objet est ajouté à un container, un test est effectué pour
     * vérifier que l'action WordPress en cours correspond au hook indiqué
     * ici et une erreur est générée si ce n'est pas le cas.
     *
     * C'est la méthode parent() qui fait ce test.
     *
     * @return string le nom de l'action WordPress (init, admin_menu, etc.)
     * qui doit être utilisée pour créer des objets de ce type.
     */
    public function hookName() {
        return $this->hookName;
    }
}