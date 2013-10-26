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
 * @version     SVN: $Id: AbstractActions.php 506 2013-03-13 16:30:08Z
 * daniel.menard.35@gmail.com $
 */

namespace Docalist;

use Docalist\Http\Response;
use Docalist\Http\ViewResponse;
use Docalist\Http\RedirectResponse;
use Docalist\Http\JsonResponse;

use ReflectionObject, ReflectionMethod;

use Exception;

/**
 * Un contrôleur permet de regrouper plusieurs actions ensemble et d'y
 * accéder à partir d'un point d'entrée (une url).
 *
 * Chaque objet contrôleur a un identifiant unique (id) et les actions sont
 * les méthodes (public ou protected) de cet objet ayant le prefixe 'action'.
 *
 * Lorsque l'utilisateur appelle l'url du contrôleur, la méthode correspondante
 * est appellée en passant en paramètre les arguments fournis dans la requête.
 *
 * L'action doit retourner un objet Response qui est envoyé au navigateur.
 *
 * Par défaut, la classe Controller utilise le "generic post handler" mis en
 * place dans WordPress 2.6 et utilise le fichier wp-admin/admin.php comme
 * point d'entrée mais les classes descendantes peuvent changer ça (par
 * exemple, la classe AdminPage utilise les entrées de menu comme point
 * d'entrée).
 *
 * Exemple :
 * Si un contrôleur avec l'ID 'test-ctrl' contient une méthode :
 *
 * <code>
 *     public function actionHello($name = 'world') {
 *         return $this->view('my-plugin:hello.php', ['name' => $name]);
 *     }
 * </code>
 *
 * on pourra accéder à cette action (si on les droits requis) avec l'url :
 *
 * <code>
 *     wordpress/wp-admin/admin.php?action=test-ctrl&m=Hello&name=guest
 *
 *     // ou bien, en utilisant la valeur par défaut des paramètres :
 *     wordpress/wp-admin/admin.php?action=test-ctrl&m=Hello
 * </code>
 *
 * Inversement, il est possible de générer un lien vers cette action avec :
 *
 * <code>
 *     $this->url('Hello', 'guess');
 *     // ou
 *     $this->url('Hello', ['name' => 'guess']);
 * </code>
 *
 * @see
 * - http://core.trac.wordpress.org/ticket/7283
 * - http://core.trac.wordpress.org/changeset/8315
 */
class Controller {
    /**
     * Identifiant unique de ce contrôleur.
     *
     * @var string
     */
    protected $id;

    /**
     * Nom de la page qui sert de point d'entrée pour exécuter les actions
     * de ce contrôleur.
     *
     * Par défaut, il s'agit de 'admin.php'. Pour une page d'admin ajoutée
     * dans un sous menu, il s'agit de la page du menu.
     *
     * @var string
     */
    protected $parentPage;

    /**
     * Nom du paramètre passé en query string qui contient l'ID du controlleur.
     *
     * Par défaut, c'est 'action' (c'est ce qu'attend le script wordpress
     * admin.php) mais pour une page ajoutée dans un menu, c'est 'page'.
     *
     * Les urls générées tiennent compte de ce paramètre. Exemples :
     * wordpress/wp-admin/admin.php?action=docalist-search-actions
     * wordpress/wp-admin/options-general.php?page=docalist-biblio-settings
     *
     * @var string
     */
    protected $controllerParameter = 'action';

    /**
     * Nom du paramètre passé en query string qui indique l'action à exécuter.
     *
     * Paramétrable au cas où on ait un jour un conflit de nom avec des
     * arguments utilisés par wordpress.
     *
     * @var string
     */
    protected $actionParameter = 'm'; // m comme "method"

    /**
     * Nom de l'action par défaut de ce contrôleur.
     *
     * Il s'agit de l'action qui sera exécutée si aucune action n'est indiquée
     * dans les paramètres de la requête.
     *
     * @var string
     */
    protected $defaultAction = 'Index';

    /**
     * Définit les droits requis pour exécuter les actions de ce contrôleur.
     *
     * Le tableau est de la forme "nom de l'action" => "capacité requise".
     *
     * Chacune des clés identifie l'une des actions du contrôleur (il faut
     * respected la casse exacte du nom de la méthode correspondante).
     *
     * La clé "default" indique la capacité requise ('manage_options' par
     * défaut) pour que le contrôleur soit visible (par exemple, une page
     * d'admin n'apparaîtra pas dans le menu si l'utilisateur n'a pas ce droit).
     *
     * Elle sert également de capacité par défaut pour les actions qui ne
     * figurent pas dans le tableau.
     *
     * @var array
     */
    protected $capability = [];

    /**
     * Initialise le contrôleur.
     *
     * @param string $id Identifiant unique du contrôleur.
     * @param string $parentPage Url de la page parent.
     */
    public function __construct($id, $parentPage = 'admin.php') {
        $this->id = $id;
        $this->parentPage = $parentPage;
        $this->register();
    }

    /**
     * Enregistre le contrôleur dans Wordpress.
     */
    protected function register() {
        if ($this->canRun()) {
            add_action('admin_action_' . $this->id(), function() {
                $this->run()->send();
                exit();
            });
        }
    }

    /**
     * Retourne l'identifiant du contrôleur.
     *
     * @return string
     */
    protected function id() {
        return $this->id;
    }

    /**
     * Retourne l'url de la page parent.
     *
     * @return string
     */
    protected function parentPage() {
        return $this->parentPage;
    }

    /**
     * Retourne le nom de l'action par défaut de ce contrôleur.
     *
     * Il s'agit de l'action qui sera exécutée si aucune action n'est indiquée
     * dans les paramètres de la requête.
     *
     * @return string
     */
    protected function defaultAction() {
        return $this->defaultAction;
    }

    /**
     * Retourne le nom de l'action exécutée.
     *
     * @return string
     */
    protected function action() {
        if (isset($_REQUEST[$this->actionParameter])) {
            return $_REQUEST[$this->actionParameter];
        }

        return $this->defaultAction();
    }

    /**
     * Retourne le nom de la méthode de ce contrôleur qui implémente l'action
     * passée en paramètre.
     *
     * Le nom de la méthode correspond au nom de l'action auquel est ajouté
     * le préfixe "action".
     *
     * @param string $action Nom de l'action. Optionnel, utilise l'action en
     * cours si absent.
     *
     * @return string
     */
    protected function method($action = null) {
        return 'action' . ($action ?: $this->action());
    }

    /**
     * Retourne la liste des actions de ce module.
     *
     * Par défaut, la méthode ne retourne que les actions qui peuvent être
     * appellées sans arguments (i.e. la méthode correspondant à l'action n'a
     * aucun paramètre ou bien ils ont tous une valeur par défaut) et qui sont
     * 'public'.
     *
     * @param bool $callableOnly Ne retourne que les actions qui peuvent être
     * appellées sans arguments (i.e. la méthode correspondant à l'action n'a
     * aucun paramètre ou bien ils ont tous une valeur par défaut).
     *
     * @param bool $hasCapacity Ne retourne que les actions pour lesquelles
     * l'utilisateur a les droits requis.
     *
     * @param bool $publicOnly Ne retourne que les actions dont la méthode
     * est marquée 'public' (false : retourne aussi les méthodes marquées
     * 'protected').
     *
     * @param bool $includeDefault Inclure ou non  l'action par défaut dans la
     * liste des actions.
     *
     * @return array
     */
    protected function actions($callableOnly = true, $hasCapacity = true, $publicOnly = true, $includeDefault = false) {
        $actions = [];

        $class = new ReflectionObject($this);

        $flags = ReflectionMethod::IS_PUBLIC;
        !$publicOnly && $flags |= ReflectionMethod::IS_PROTECTED;

        foreach ($class->getMethods($flags) as $method) {
            $name = $method->getName();

            // On ne liste que les actions
            if (strncmp($name, 'action', 6) !== 0) {
                continue;
            }

            $action = substr($name, 6);

            // Ignore les méthodes action() et actions()
            if (empty($action) || $action==='s') {
                continue;
            }

            // Ignore l'action par défaut
            if (! $includeDefault && $name === $this->defaultAction()) {
                continue;
            }

            // Ne liste que les action que l'utilisateur peut appeller
            if ($hasCapacity && !$this->canRun($action)) {
                continue;
            }

            // On ne peut appeller que les méthodes sans paramètres requis
            if ($callableOnly && $method->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            // Stocke le nom de la méthode
            $actions[] = $action;
        }

        return $actions;
    }

    /**
     * Retourne la capability WordPress requise pour exécuter une action.
     *
     * @param string $action Nom de l'action à tester ou 'default' pour
     * retourner la capacité par défaut du contrôleur.
     *
     * @return string
     *
     * @see http://codex.wordpress.org/Roles_and_Capabilities
     */
    protected function capability($what = 'default') {
        // Teste si un droit a été indiqué pour cette action
        if (isset($this->capability[$what])) {
            return $this->capability[$what];
        }

        // Pas de droit spécifique à l'action, retourne la capacité par défaut
        if (isset($this->capability['default'])) {
            return $this->capability['default'];
        }

        // Pas de capacité par défaut
        return 'manage_options';
    }

    /**
     * Indique si l'utilisateur peut exécuter l'action indiquée.
     *
     * @param string $action Nom de l'action à tester ou 'default' pour
     * retourner la capacité par défaut du contrôleur.
     *
     * @return bool
     */
    protected function canRun($what = 'default') {
        return current_user_can($this->capability($what));
    }

    /**
     * Construit un tableau avec les paramètres à transmettre à la méthode
     *
     * @param ReflectionMethod $method
     * @param array $args
     * @param bool $checkTooManyArgs Génère une exception si $args contient des
     * paramètres qui ne firgurent pas dans la méthode.
     *
     * @return array
     *
     * @throws Exception
     * - si un paramètre obligatoire est absent
     * - s'il y a trop de paramètres et que $checkTooManyArgs est à true
     */
    protected function matchParameters(ReflectionMethod $method, array $args, $checkTooManyArgs = false) {
        $params = $method->getParameters();
        $result = [];
        foreach ($params as $i => $param) {
            // Récupère le nom du paramètre
            $name = $param->getName();

            // TODO : si name="request', transmettre un objet Request

            // Le paramètre peut être fourni soit par nom, soit par numéro
            $key = isset($args[$name]) ? $name : $i;

            // Le paramètre a été fourni
            if (isset($args[$key])) {
                $value = $args[$key];

                // La méthode attend un tableau, caste en array
                if ($param->isArray() && !is_array($value)) {
                    $value = array($value);
                }

                // Tout est ok
                $result[$name] = $value;
                unset($args[$key]);
                continue;
            }

            // Paramètre non fourni : utilise la valeur par défaut s'il y en a une
            if (!$param->isDefaultValueAvailable()) {
                $msg = __("Paramètre requis : %s.", 'docalist-core');
                throw new Exception(sprintf($msg, $name));
            }

            // Ok
            $result[$name] = $param->getDefaultValue();
        }

        if ($checkTooManyArgs && count($args)) {
            $msg = __('Trop de paramètres (%s)', 'docalist-core');
            $msg = sprintf($msg, implode(', ', array_keys($args)));

            throw new Exception($msg);
        }

        return $result;
    }

    /**
     * Lance l'exécution d'une action.
     *
     * @return Response la valeur retournée par la méthode exécutée.
     */
    protected function run() {
        // Récupère l'action à exécuter
        $action = $this->action();

        // Détermine la méthode à appeller
        $name = $this->method();

        // Vérifie que la méthode demandée existe
        $class = new ReflectionObject($this);
        if (!$class->hasMethod($name)) {
            return $this->view(
                'docalist-core:controller/action-not-found',
                ['action' => $action],
                404
            );
        }

        // Vérifie qu'on peut l'appeller
        $method = $class->getMethod($name);
        if ($method->isPrivate() || $method->isStatic()) {
            $msg = __('La méthode <code>%s</code> est "%s".', 'docalist-core');
            $msg = sprintf($msg, $name, $method->isPrivate() ? 'private' : 'static');
            return $this->view(
                'docalist-core:controller/bad-request',
                ['message' => $msg],
                400
            );
        }

        // Remarques : autres flags possibles
        // isConstructor(), isDestructor() : impossible car le nom ne commence
        // par 'action' ; isAbstract() : on n'aurait pas pu créer l'instance

        // Récupère la casse exacte de l'action
        // C'est important car sinon on pourrait court-circuiter les droits
        // en changeant la casse de l'action en query string : comme les
        // méthodes php sont insensibles à la casse, cela marcherait.
        $action = substr($method->getName(), strlen('action'));

        // Vérifie que l'utilisateur a les droits requis pour exécuter l'action
        if (! $this->canRun($action)) {
            return $this->view(
                'docalist-core:controller/access-denied',
                ['action' => $action],
                403 // 401 ou 403 ? @see http://stackoverflow.com/a/8469124
            );
        }

        // Construit la liste des paramètres
        try {
            $args = $this->matchParameters($method, $_REQUEST, false);
        } catch (Exception $e) {
            return $this->view(
                'docalist-core:controller/bad-request',
                ['message' => $e->getMessage()],
                400
            );
        }

        // Si l'action est protected (action appellable mais non listée dans
        // actionIndex), il faut la rendre accessible
        if ($method->isProtected()) {
            $method->setAccessible(true); // oui, c'est monstrueux de pouvoir faire ça...
        }

        // Appelle la méthode avec la liste d'arguments obtenue
        return $method->invokeArgs($this, $args);
    }

    /**
     * Retourne l'url à utiliser pour appeller une action de ce contrôleur.
     *
     * Exemples :
     * - url() : retourne l'url en cours ou l'url par défaut
     * - url('Action') : url d'une action qui n'a pas de paramètres ou dont
     *   tous les paramètres ont une valeur par défaut.
     * - url('Action', array('arg1'=>1, 'arg2' => 2)) : les paramètres sont
     *   passés dans un tableau
     * - url('Action', 'arg1', 'arg2') : les paramètres sont dans l'ordre
     *   attendu par l'action.
     *
     * @param string $action
     * @param mixed $args un tableau contenant les arguments ou une liste de
     * paramètres.
     *
     * @return string
     * @throws Exception
     * - si l'action indiquée n'existe pas
     * - si la méthode qui implémente l'action est 'private' ou 'static'
     * - si l'utilistaeur en cours n'a pas les droits suffisants
     * - si un paramètre est obligatoire mais n'a pas été fourni
     * - s'il y a trop de paramètres fournis ou des paramètres qui ne sont pas
     *   dans la signature de la méthode de l'action.
     */
    protected function url($action = null, $args = null) {
        // Récupère l'action et le nom de la méthode correspondante
        empty($action) && $action = $this->action();
        $name = $this->method($action);

        // Vérifie que la méthode existe
        $class = new ReflectionObject($this);
        if (!$class->hasMethod($name)) {
            $msg = __("L'action %s n'existe pas", 'docalist-biblio');
            throw new Exception(sprintf($msg, $action));
        }

        // Vérifie qu'on peut appeller cette méthode
        $method = $class->getMethod($name);
        if ($method->isPrivate() || $method->isStatic()) {
            $msg = __('La méthode %s est "%s".', 'docalist-core');
            $msg = sprintf($msg, $name, $method->isPrivate() ? 'private' : 'static');
            throw new Exception(sprintf($msg, $name));
        }

        // Récupère la casse exacte de l'action
        $action = substr($method->getName(), strlen('action'));

        // Vérifie que l'utilisateur a les droits requis pour l'action
        if (! $this->canRun($action)) {
            $msg = __("Vous n'avez pas les droits requis pour faire un lien vers l'action %s.", 'docalist-biblio');
            throw new Exception($msg, $action);
        }

        // Construit la liste des paramètres de la méthode
        $args = func_get_args();
        array_shift($args);
        count($args) === 1 && is_array($args[0]) && $args = $args[0]; // // Appel de la forme "action, array(args)"
        $args = $this->matchParameters($method, $args, true);

        // Ajoute les paramètres du contrôleur
        $t = [$this->controllerParameter => $this->id()];
        $action !== $this->defaultAction() && $t[$this->actionParameter] = $action;
        $args = $t + $args;

        // Retourne l'url
        return add_query_arg($args, admin_url($this->parentPage()));
    }

    /**
     * Indique si la requête en cours est une requête POST.
     *
     * @return boolean
     */
    protected function isPost() {
        return $_SERVER['REQUEST_METHOD'] == 'POST';
    }

    /**
     * Indique si la requête en cours est une requête GET.
     *
     * @return boolean
     */
    protected function isGet() {
        return $_SERVER['REQUEST_METHOD'] == 'GET';
    }

    /**
     * Retourne une réponse de type ViewResponse.
     *
     * @param string $view
     * @param array $viewArgs
     * @param int $status
     * @param array $headers
     *
     * @return ViewResponse
     */
    protected function view($view, array $viewArgs = array(), $status = 200, $headers = array()){
        !isset($viewArgs['this']) && $viewArgs['this'] = $this;
        return new ViewResponse($view, $viewArgs, $status, $headers);
    }

    /**
     * Retourne une réponse de type RedirectResponse.
     *
     * @param string $url
     * @param int $status
     * @param array $headers
     *
     * @return RedirectResponse
     */
    protected function redirect($url, $status = 302, $headers = array()) {
        return new RedirectResponse($url, $status, $headers);
    }

    /**
     * Retourne une réponse de type JsonResponse.
     *
     * @param mixed $content
     * @param int $status
     * @param array $headers
     *
     * @return JsonResponse
     */
    protected function json($content = '', $status = 200, $headers = array()) {
        return new JsonResponse($content, $status, $headers);
    }
}