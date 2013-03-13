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
use ReflectionObject, ReflectionMethod, Exception;
use Docalist\Forms\Form;

/**
 * Représente un ensemble d'actions pour un plugin.
 *
 * AbstractActions permet de regrouper dans un objet unique différentes
 * actions d'administration propres à un plugin. Les actions sont
 * enregistrées dans wordpress via le hook admin_action_{$action} et
 * utilisent le "generic post handler" mis en place dans WordPress 2.6.
 *
 * Les actions ne sont pas visibles de l'utilisateur (elles n'apparaissent
 * pas dans le menu WordPress). Simplement, elles sont disponibles et peuvent
 * être appellées via une url de la forme
 * wordpress/wp-admin/admin.php?action=<votre id>&m=<votre méthode>
 *
 * Pour ajouter des actions visibles et créer de nouvelles options dans
 * le menu de WordPress, héritez plutôt de la classe AbstractAdminPage.
 *
 * AbstractActions se charge de rendre accessibles toutes les méthodes
 * de votre classe dont le nom commence par le préfixe "action", qu'elles
 * soient publiques ou protected.
 *
 * Par exemple, si vous avez une méthode nommée actionHello(), vous pouvez
 * appeller wordpress/wp-admin/tools.php?page=<votre id>&m=Hello
 *
 * Les arguments transmis en query string dont le nom correspond à un
 * paramètre de votre méthode sont automatiquement transmis. Vous pouvez
 * également donner une valeur par défaut aux paramètres de votre méthode
 * si tous les arguments ne sont pas transmis.
 *
 * Exemple :
 * <code>
 * wordpress/wp-admin/tools.php?page=id&m=Hello&nom=Martin&prenom=Jacques
 * public function actionHello($nom, $prenom, $civilite='M.') {
 *     echo "Bonjour $civilite $prenom $nom !"; // Bonjour M. Jacques Martin
 * }
 * </code>
 *
 * Une erreur est générée si les arguments passés en query string ne
 * permettent pas d'appeller votre action :
 * - l'action indiquée ne désigne pas une méthode de votre classe
 * - paramètres obligatoire snon fournis en query string
 *
 * Lorsque votre méthode commence à s'exécuter, aucun contenu n'a été envoyé
 * au navigateur (même pas un content-type). Il vous appartient :
 * - de gérer la sécurité : est-ce que l'utilisateur a le droit
 *   d'exécuter votre action ?
 * - de valider les données reçues (type des paramètres, etc.)
 * - de générer le contenu intégral de la page (content-type, charset, page
 *   html complète, redirection, etc.)
 *
 * Votre module contient automatiquement une action "index" (actionIndex())
 * qui permet d'accéder à toutes les actions publiques de votre module pour
 * lesquelles aucun paramètre n'est obligatoire.
 *
 * Remarque : toutes les actions (public ou protected) peuvent être appellées
 * mais seules les actions public sont listées dans index et seulement si
 * elles peuvent être appellées sans paramètres.
 *
 * Souvent vous voudrez demander à l'utilisteur une confirmation avant de
 * lancer l'action. Pour ça, la classe fournit une méthode confirm().
 *
 * Si vous avez besoin de demander des paramètres, vous pouvez utiliser ask().
 *
 * Sécurité :
 * Seuls les utilisateurs connectés peuvent accéder à vos actions (c'est
 * WordPress qui vérifie l'authentification dans la page admin.php).
 *
 * Par défaut, seuls les utilisateurs qui disposent de la capacité
 * "manage_options" peuvent accéder à vos actions. Pour chaque action,
 * vous pouvez indiquer la capacité requise en ajoutant une annotation de
 * la forme "@capability xxx" au docblock de l'action.
 *
 * La capacité de l'action index sert de capacité par défaut pour toutes
 * les actions qui n'ont pas une capacité spécifique de définie.
 *
 * Vous pouvez utiliser une capacité très faible pour élargir l'accès aux
 * actions. Par exemple "@capacity read" permettra à n'importe quel
 * utilisateur connecté de lancer l'action.
 *
 * C'est utile, entres autres, pour accéder à une action depuis le front
 * office (génération d'un fichier, etc.)
 *
 * @see
 * - http://core.trac.wordpress.org/ticket/7283
 * - http://core.trac.wordpress.org/changeset/8315
 */
abstract class AbstractActions extends Registrable {
    /**
     * @var string nom du paramètre de la query string qui contiendra le
     * nom de l'action ou de la page à exécuter dans l'url (cf {@link url()}).
     *
     * Cette propriété est statique (elle ne dépend pas de l'instance) et
     * elle n'est pas destinée à être surchargée par les utilisateurs.
     *
     * Pour les AdminPage la valeur est 'page', ce qui génère des urls de
     * la forme wordpress/wp-admin/tools.php?page=$this->id()
     *
     * Pour les AdminActions, la valeur est 'action', ce qui génère des
     * urls de la forme wordpress/wp-admin/admin.php?action=$this->id()
     */
    static protected $parameterName = 'action';

    /**
     * @var string Slug de la page (du menu) où sera rattachée la page.
     *
     * cf. http://codex.wordpress.org/Function_Reference/add_submenu_page
     *
     * Si null, la page est ajoutée comme menu de premier niveau.
     */
    protected $parentPage = 'admin.php';

    /**
     * @inheritdoc
     */
    public function register() {
        if (current_user_can($this->capability())) {
            add_action('admin_action_' . $this->id(), function() {
                $this->run();
            });
        }
    }

    /**
     * Retourne le nom de l'action en cours.
     *
     * Le nom de l'action est passé en query string (ou dans les données
     * $_POST) dans le paramètre "m" (comme méthode).
     *
     * Si aucune action n'a été indiquée, la méthode retourne "Index".
     *
     * @return string
     */
    public function action() {
        return isset($_REQUEST['m']) ? $_REQUEST['m'] : 'Index';
    }

    /**
     * Retourne le nom de la méthode en cours.
     *
     * Le nom de la méthode correspond au nom de l'action auquel est ajouté
     * le préfixe "action".
     *
     * Si aucune action n'a été indiquée, la méthode retourne "actionIndex".
     *
     * @return string
     */
    public function method() {
        return 'action' . $this->action();
    }

    /**
     * Retourne la capability WordPress requise pour exécuter une action.
     *
     * Les capabilities sont définies via des annotations "@capability"
     * ajoutées au docblock des actions. Exemple : @capability manage_options
     *
     * Si aucune capability n'a été définie pour une action donnée, c'est
     * celle indiquée pour la méthode actionIndex() qui est prise en compte.
     *
     * Si aucune capability n'a été définie pour l'action index, la méthode
     * retourne "manage_options", ce qui signifie que l'action ne pourra être
     * exécutée que par un administrateur.
     *
     * @param string $action
     *
     * @return string
     *
     * @see http://codex.wordpress.org/Roles_and_Capabilities
     */
    public function capability($action = null) {
        // Si aucune action n'a été indiquée, on prend l'action en cours
        is_null($action) && $action = $this->action();

        // Extrait la capacité depuis le docblock de l'action
        $tags = DocBlock::ofMethod($this, "action$action")->tags;
        if (isset($tags['capability'])) {
            return $tags['capability'][0];
        }

        // Retourne la capacité par défaut du module
        return $this->defaultCapability();
    }

    /**
     * Retourne la capacit par défaut requise pour exécuter une des actions
     * du module.
     *
     * La capacité est lue à partir de l'annotation @capability présente
     * dans le docblock de la classe.
     *
     * Si la classe n'a pas définit de capacité, la méthode retourne
     * "manage_options" (i.e. accès restreint aux administrateurs).
     *
     * @return string
     */
    public function defaultCapability() {
        $tags = DocBlock::ofClass($this)->tags;
        if (isset($tags['capability'])) {
            return $tags['capability'][0];
        }

        // Aucune capacité définie nulle part : accès administrateurs uniquement
        return 'manage_options';
    }

    /**
     * Retourne l'url d'une action.
     *
     * @param string $action
     *
     * @return string
     */
    public function url($action = null) {
        is_null($action) && $action = $this->action();
        $url = admin_url($this->parentPage ? : 'admin.php');
        $args = array(static::$parameterName => $this->id());
        $action !== 'Index' && $args['m'] = $action;

        return add_query_arg($args, $url);
    }

    /**
     * Retourne le titre d'une action, extrait à partir du DocBlock de la
     * méthode.
     *
     * @param string $action
     *
     * @return string
     */
    protected function title($action = null) {
        is_null($action) && $action = $this->action();
        if ($action === 'Index') {
            return $this->pageTitle();
        }
        $title = DocBlock::ofMethod($this, "action$action")->desc;
        if (is_null($title)) {
            return $action;
        }
        if (preg_match('~^(.*?)[\r\n]{2}~s', $title, $match)) {
            return $match[1];
        }

        return $title;
    }

    /**
     * Retourne la description d'une action, extraite à partir du
     * DocBlock de la méthode.
     *
     * @param string $action
     *
     * @return string
     */
    protected function description($action = null) {
        is_null($action) && $action = $this->action();

        if ($action === 'Index') {
            return '';
        }

        $description = DocBlock::ofMethod($this, "action$action")->desc;
        if (!preg_match('~[\r\n]{2}(.*)~s', $description, $match)) {
            return '';
        }
        $description = preg_replace('~[\r\n]{2}~', '<br /><br />', $match[1]);

        return $description;
    }

    /**
     * Lance l'exécution d'une action.
     *
     * @return mixed la valeur retournée par la méthode exécutée.
     */
    protected function run() {
        // Détermine la méthode à appeller
        $name = $this->method();

        // Vérifie que la méthode demandée existe
        $class = new ReflectionObject($this);
        if (!$class->hasMethod($name)) {
            return $this->notFound($name);
        }

        // Vérifie qu'on peut l'appeller
        $method = $class->getMethod($name);
        if ($method->isPrivate()) {
            return $this->badRequest("Method <code>$name</code> is private.");
        }
        if ($method->isStatic()) {
            return $this->badRequest("Method <code>$name</code> is static.");
        }

        // isConstructor(), isDestructor() : pas possible (le nom commence par
        // action)
        // isabstract() : pas possible, on n'aurait pas pu créer l'instance

        // Si l'action est protected, il faut la rendre accessible
        if ($method->isProtected()) {
            $method->setAccessible(true);
        }

        // Construit un tableau args contenant les paramètres à transmettre
        $params = $method->getParameters();
        $args = array();
        foreach ($params as $param) {
            // Récupère le nom du paramètre
            $name = $param->getName();

            // Le paramètre figure dnas la query string
            if (isset($_REQUEST[$name])) {
                $value = $_REQUEST[$name];

                // Ignore les paramètres vides
                if ($value !== '' && !is_null($value)) {

                    // La méthode attend un tableau, caste en array
                    if ($param->isArray() && !is_array($value)) {
                        $args[$name] = array($value);
                        continue;
                    }

                    // Tout est ok
                    $args[$name] = $value;
                    continue;
                }
            }

            // Utilise la valeur par défaut s'il y en a une
            if (!$param->isDefaultValueAvailable()) {
                return $this->badRequest("Parameter <code>$name</code> is required.");
            }

            // Ok
            $args[$name] = $param->getDefaultValue();
        }

        // Appelle la méthode avec la liste d'arguments obtenus
        return $method->invokeArgs($this, $args);
    }

    /**
     * Liste des outils disponibles
     *
     * Liste toutes les actions publiques du module.
     *
     * i.e. les méthodes publiques, dont le nom commence par le préfixe
     * "action", et qui peuvent être appellées sans paramètres (aucun
     * paramètre ou paramètres ayant une valeur par défaut).
     */
    public function actionIndex() {
        $list = array();
        $class = new ReflectionObject($this);
        foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->getName();

            // Inutile de lister l'action index
            if ($name === 'actionIndex') {
                continue;
            }

            // On ne liste que les méthode dont le nom commence par "action"
            if (strncmp($name, 'action', 6) !== 0) {
                continue;
            }

            // Extrait le nom de l'action du nom de la méthode
            $name = substr($name, 6);

            // Ignore la méthode action(), ce n'est pas une action
            if (empty($name)) {
                continue;
            }

            // Ne liste que les action que l'utilisateur peut appeller
            if (! current_user_can($this->capability($name))) {
                continue;
            }

            // On ne peut appeller que les méthodes sans paramètres requis
            if ($method->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            // Stocke le nom de la méthode
            $list[] = $name;
        }

        if (empty($list)) {
            echo "<p>Aucune action n'est disponible dans ce module.</p>";
            return;
        }

        sort($list);

        echo '<ul class="ul-disc">';
        foreach ($list as $name) {
            $url = $this->url($name);
            $title = $this->title($name) ? : $name;
            $description = $this->description($name);
            echo '<li>';
            printf('<h3><a href="%s">%s</a></h3>', $url, rtrim($title, '. '));
            if ($description) {
                echo '<p class="description">', $description, '</p></dd>';
            }
            echo '</li>';
        }
        echo '</ul>';
    }

    // adapté de AbstractSettingsPage
    protected function pageTitle() {
        return ucfirst(strtr($this->parent->id(), '-', ' '));
    }

    /**
     * Affiche un mini-formulaire.
     *
     * On peut appeller
     * - en fournissant chacun des arguments
     * - en passant un tableau unique contenant les arguments
     * - en passant un tableau unique contenant des tableus d'arguments
     *
     * @param string $type
     * @param string $name
     * @param string $label
     * @param string $description
     * @param array $attributes
     */
    protected function ask($type, $name = null, $label = null, $description = null, $attributes = null) {
        $args = is_array($type) ? $type : func_get_args();

        if (is_string(current($args))) {
            $args = array($args);
        }

        $form = new Form('', 'GET');
        $form->hidden(static::$parameterName);
        $form->hidden('m');
        foreach ($args as $arg) {
            if (is_int(key($arg))) {
                if (count($arg) < 2) {
                    throw new Exception('Arguments incorrects pour ask, vous devez indiquer au moins type et name');
                }
                $t = array(
                    'type' => $arg[0],
                    'name' => $arg[1],
                    'label' => isset($arg[2]) ? $arg[2] : $arg[1],
                );
                isset($arg[3]) && $t['description'] = $arg[3];
                isset($arg[4]) && $t['attributes'] = $arg[4];

                $arg = $t;
            } else {
                if (!isset($arg['type']) || !isset($arg['name'])) {
                    throw new Exception('Argument incorrect pour ask, vous devez indiquer au moins type et name');
                }
                !isset($arg['label']) && $arg['label'] = $arg['name'];
            }

            $field = $form->add($arg['type'], $arg['name'])->label($arg['label']);
            isset($arg['description']) && $field->description($arg['description']);
            isset($arg['attributes']) && $field->attributes($arg['attributes']);
        }
        $form->submit('Go !');

        $form->bind($_REQUEST);
        $form->render('wordpress');
    }

    /**
     * Demande confirmation à l'utilisateur.
     *
     * @param string $message Le message à afficher.
     * @return bool True si l'utilisateur a déjà confirmé, false sinon.
     */
    public function confirm($message = null) {
        if (isset($_REQUEST['confirm']) && $_REQUEST['confirm'] === '1') {
            return true;
        }

        if ($message) {
            printf('<p><strong>%s</strong></p>', $message);
        }
        $this->ask('hidden', 'confirm', '', '', array('value' => '1'));

        return false;
    }

    /**
     * Génère une réponse http "404 - Not Found".
     *
     * @param string $method le nom de la méthode non trouvée.
     * @return false
     */
    protected function notFound($method) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
        header('Status: 404 Not Found');
        header('Content-type: text/html; charset=UTF-8');
        echo "<h1>404 Not Found</h1>\n";
        echo "<p>Method <code>$method</code> does not exist.</p>\n";

        return false;
    }

    /**
     * Génère une réponse http "400 - Bad Request".
     *
     * @param string $reason La raison pour laquelle la requête est incorrecte.
     * @return false
     */
    protected function badRequest($reason = '') {
        header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
        header('Status: 400 Bad Request');
        header('Content-type: text/html; charset=UTF-8');
        echo "<h1>400 Bad Request</h1>\n";
        if ($reason) {
            echo "<p>$reason</p>\n";
        }

        return false;
    }

}
