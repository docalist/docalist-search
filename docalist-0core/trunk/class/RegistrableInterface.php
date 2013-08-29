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
 * Représente un objet enregistrable.
 *
 * Un registrable est un objet nommé (il a un nom et un id) qui a un moment
 * ou un autre (cf. {@link hookName}) est enregistré (déclaré) dans WordPress
 * via sa méthode register() (cf. {@link register}) : plugin, post type,
 * taxonomie, metabox, etc.
 *
 * Les registrables sont destinés à être ajoutés à un container
 * (cf. {@link ContainerInterface}) qui est lui-même un registrable. L'ensemble
 * des registrables forme ainsi un arbre dont la racine est le plugin qui les
 * définit. Tous les registrables ont ainsi accès aux méthodes du plugin dont
 * ils dépendent et notamment à ses options de configuration
 * (cf. {@link settings}).
 */
interface RegistrableInterface {
    /**
     * Initialise et enregistre l'objet dans WordPress.
     *
     * Cette méthode est appellée lorsque l'objet est ajouté à un container.
     *
     * (cf {@link ContainerTrait::add()}).
     */
    public function register();

    /**
     * Retourne ou modifie le container parent de cet objet.
     *
     * Appellée sans paramètre, la méthode retourne le container parent de
     * l'objet ou null si l'objet n'a pas de parent.
     *
     * Utilisée comme Setter, elle enregistre le containeur passé en paramètre
     * comme parent de l'objet.
     *
     * Remarque :
     *
     * Une fois que le parent d'un objet Registrable a été définit, il ne peut
     * plus être modifié. Une exception sera levée si on essaie de modifier le
     * parent d'un objet Registrable qui a déjà un parent.
     *
     * @param ContainerInterface $container Le container parent de l'objet.
     *
     * @throws Exception Si l'objet a déjà un parent.
     *
     * @return ContainerInterface|$this Retourne le container parent de l'objet
     * quand la méthode est appellée sans paramètres ou $this si la méthode est
     * utilisée comme Setter.
    */
    public function parent(ContainerInterface $parent = null);

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
    public function plugin();

    /**
     * Retourne l'identifiant unique de cet objet.
     *
     * La majorité des objets enregistrables sont capables de construire
     * automatiquement leur id en combinant le nom de leur classe avec le
     * nom de leur container.
     *
     * Dans certains cas, l'id de l'objet est définit explicitement pour
     * pouvoir être utilisé de façon transversale dans WordPress
     * (c'est le cas par exemple le nom d'un custom post type ou d'une
     * taxonomie).
     *
     * @return string
    */
    public function id();

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
    public function settings();

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
     *
     * <code>
     * $this->setting('options.display.contrast')
     * </code>
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
    public function setting($setting);

    /**
     * Retourne le nom du hook qui doit être utilisé pour créer des objets
     * de ce type.
     *
     * Cette méthode n'est appellée qu'en mode debug.  Elle permet à vérifier
     * que les objets sont créés "au bon moment" dans WordPress (par exemple
     * ne pas créer des pages d'administration quand on est coté front-office).
     *
     * Lorsque l'objet est ajouté à un container, un test est effectué pour
     * vérifier que l'action WordPress en cours correspond au hook indiqué
     * ici et une erreur est générée si ce n'est pas le cas.
     *
     * @return string le nom de l'action WordPress (init, admin_menu, etc.)
     * qui doit être utilisée pour créer des objets de ce type.
    */
    public function hookName();
}