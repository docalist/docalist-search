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

use Docalist\Http\AdminViewResponse;
use Docalist\Http\Response;

/**
 * Une page d'administration dans le back-office.
 */
abstract class AbstractAdminPage extends AbstractActions {
    /**
     * {@inheritdoc}
     */
    static protected $parameterName = 'page';

    /**
     * {@inheritdoc}
     */
    protected $menuTitle;

    /**
     *
     * @param string $parentPage Url de la page parent.
     * @param string $pageTitle Titre de la page.
     * @param string $menuTitle Libellé de la page utilisé dans le menu.
     */
    public function __construct($parentPage = '', $pageTitle = '', $menuTitle = '') {
        parent::__construct($parentPage, $pageTitle);
        $this->menuTitle = $menuTitle;
    }

    /**
     * Retourne le titre de la page affiché dans le menu.
     *
     * @return string
     */
    protected function menuTitle() {
        return $this->menuTitle ?: $this->pageTitle();
    }

    /**
     * {@inheritdoc}
     */
    public function register() {
        // On ne fait rien si l'utilisateur n'a pas les droits requis
        $capability = $this->defaultCapability();
        if (! current_user_can($capability)) {
            return;
        }

        // Crée la page dans le menu WordPress
        $parent = $this->parentPage();
        if (empty($parent)) {
            $page = add_menu_page($this->pageTitle(), $this->menuTitle(), $capability, $this->id(), function() {});
        } else {
            $page = add_submenu_page($parent, $this->pageTitle(), $this->menuTitle(), $capability, $this->id(), function() {});
        }

        /*
            Exécute l'action et affiche le résultat.

            C'est wp-admin/admin.php:145 qui appelle le hook "load-$page"
            A ce stade, rien n'a été envoyé au navigateur, même pas les entêtes.
            On exécute l'action demandée (run) et on examine le type de
            la réponse générée par l'action.

            Cas 1. Si la réponse est une page d'admin (i.e. une réponse de type
            AdminViewResponse), on se contente d'envoyer les entêtes http et
            on exécute la vue en bufferisant la sortie générée ($body).
            La vue peut ainsi faire des appels à wp_enqueue_*, génèrer des
            écrans d'aide, etc.

            Wordpress poursuit alors son exécution : il inclut admin-header.php
            qui génère les menus, l'admin bar, les screen metas, etc.

            Wordpress va ensuite appeller le hook de la page ("$page"). A ce
            stade, tout le début de la page html a été généré (on est dans
            html>body>wpwrap>wpcontent>wpbody-content) et les scripts et les
            css qui ont été ajoutés ont été générés (ceux de la partie head).

            On se contente alors d'afficher le body de la réponse qu'on avait
            bufferisé plus haut.
            Wordpress termine ensuite son exécution, génère les assets de footer
            et se termine.

            Cas 2. Si la réponse n'est pas une page d'admin (redirection,
            réponse JSON, etc.), on l'envoit directement au navigateur pendant
            le hook "load-$page" et on fait ensuite exit(), ce qui empêche
            Wordpress de continuer son exécution. Dans ce cas, seul le contenu
            de la réponse est envoyé au navigateur (pas de menu wp, etc.)
        */
        add_action("load-$page", function() use($page) {
            // Indique à l'écran en cours qui est le parent de notre page
            // Normallement, c'est admin-header.php:119 qui fait ça, mais
            // dans notre cas il n'a pas encore été appellé. Ca pose
            // problème car dans ce cas, les vues qui appellent
            // screen_icon() ne récupèrent pas la bonne icone (wp 3.6).
            get_current_screen()->set_parentage( $this->parentPage );

            // Exécute l'action, récupère la réponse générée et le garbage éventuel
            ob_start();
            $response = $this->run();
            $garbage = ob_get_clean();

            // Erreur : l'action n'a pas retourné de réponse
            if (!($response instanceof Response)) {
                add_action($page, function() use($response, $garbage) {
                    // En mode debug, on signale l'erreur
                    if (WP_DEBUG) {
                        $h3 = __("Erreur dans l'action %s", 'docalist-core');
                        $h3 = sprintf($h3, $this->action());

                        $msg = __("La méthode <code>%s()</code> devait générer un objet <code>Response</code> mais a retourné :<pre>%s</pre>", 'docalist-core');
                        $msg = sprintf($msg, $this->method(), var_export($response,true));
                        printf('<div class="error"><h3>%s</h3><p>%s</p></div>', $h3, $msg);
                    }

                    // Affiche ce qui a été généré lors de l'exécution
                    echo $garbage;
                });
            }

            // L'action a généré une réponse de type "page d'admin"
            elseif ($response instanceof AdminViewResponse) {
                // Envoie les entêtes de la réponse
                // wp ne pourra pas envoyer les siens (cf. admin-header.php)
                $response->sendHeaders();

                // Génère la réponse, mais sans l'envoyer
                // Permet à la vue de faire des "enqueue" et autres
                ob_start();
                $response->sendContent();
                $body = ob_get_clean();

                // Affiche la réponse après que wp a généré le header et les menus
                add_action($page, function() use($body, $garbage) {
                    // Si on a du garbage, on le signale en mode WP_DEBUG
                    if ($garbage && WP_DEBUG) {
                        $h3 = __("Garbage dans l'action %s", 'docalist-core');
                        $h3 = sprintf($h3, $this->action());

                        $msg = __("La méthode <code>%s()</code> a généré le contenu suivant en plus de sa réponse :<pre>%s</pre>", 'docalist-core');
                        $msg = sprintf($msg, $this->method(), var_export($garbage,true));
                        printf('<div class="error"><h3>%s</h3><p>%s</p></div>', $h3, $msg);
                    }

                    // Affiche la réponse générée
                    echo $body;
                });

                // Laisse wp générer le footer
            }

            // L'action a généré un autre type de réponse (redirect, json...)
            else {
                // Génère et envoie la réponse
                $response->send();

                // Stoppe l'exécution de wp (ni header, ni menu, ni footer)
                exit();
            }
        });
    }

    public function view($view, array $viewArgs = array(), $status = 200, $headers = array()){
        !isset($viewArgs['this']) && $viewArgs['this'] = $this;
        return new AdminViewResponse($view, $viewArgs, $status, $headers);
    }
}