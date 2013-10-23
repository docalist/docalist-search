<?php
/**
 * This file is part of the 'Docalist Core' plugin.
 *
 * Copyright (C) 2012, 2013 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Response
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     $Id$
 */
namespace Docalist\Http;
use Docalist;
use Exception;

/**
 * Une réponse dont le résultat est généré par une vue.
 *
 * Ce type de réponse permet de séparer (design pattern SOC) la préparation des
 * données (contrôleur) de leur représentation (vue).
 *
 * Le constructeur prend en paramètre le nom (symbolique) de la vue à afficher
 * et les données à transmettre à la vue.
 *
 * Le nom symbolique permet à l'utilisateur de surcharger les vues par défaut
 * en créant une vue du même nom dans le répertoire "vews" de son thème
 * (cf getViewPath()).
 */
class ViewResponse extends Response {
    protected $defaultHeaders = [
        'Content-Type' => 'text/html; charset=UTF-8',
    ];

    protected $view;
    protected $viewArgs;

    public function __construct($view, array $viewArgs = array(), $status = 200, $headers = array()) {
        parent::__construct(null, $status, $headers);

        $this->view = $this->getViewPath($view);
        $this->viewArgs = $viewArgs;
    }

    /**
     * Retourne le path de la vue ont le nom symbolique est passé en paramètre.
     *
     * La vue est d'abord recherchée dans le répertoire "/views" du thème en
     * cours, ce qui permet aux utilisateurs de surcharger les vues par défaut
     * fournies par les plugins en fournissant leur propre version.
     *
     * Si la vue n'est pas surchargée, le path de la vue par défaut fournie par
     * le plugin est retournée.
     *
     *
     * @param string $view Nom de la vue. Il s'agit d'un nom symbolique de la
     * forme {id du plugin}:{nom de la vue}. L'extension '.php' est optionnelle
     * pour le nom de la vue. La vue sera recherchée aux emplacements suivants :
     *
     * - wp-content/themes/{theme actuel}/views/{id du plugin}/{nom de la vue}
     * - wp-content/plugins/{id du plugin}/views/{nom de la vue}
     *
     * Par exemple, la vue "docalist-core:info" sera recherchée dans :
     * - wp-content/themes/twentythirteen/views/docalist-core/info.php
     * - wp-content/plugins/docalist-core/views/info.php
     *
     * @return string path de la vue
     *
     * @throws \Exception si la vue n'existe pas.
     */
    protected function getViewPath($view) {
        // Vérifie que le nom de la vue a le format attendu
        if (false === $pt = strpos($view, ':')) {
            $msg = __('Nom de vue incorrect "%s" (plugin:view attendu)', 'docalist-core');
            throw new Exception(sprintf($msg, $view));
        }

        // Sépare le nom du plugin du nom de la vue
        $plugin = substr($view, 0, $pt);
        $view = substr($view, $pt + 1);

        // Ajoute l'extendion .php si nécessaire
        pathinfo($view, PATHINFO_EXTENSION) === '' && $view .= '.php';

        // Teste si la vue existe dans le thème en cours
        $path = get_template_directory() . "/views/$plugin/$view" ;
        if (file_exists($path)) {
            return $path;
        }

        // Teste si la vue existe dans le plugin
        $path = Docalist::get($plugin)->directory() . "/views/$view";
        if (file_exists($path)) {
            return $path;
        }

        // 404
        $msg = __('Vue non trouvée : "%s:%s"', 'docalist-core');
        throw new \Exception(sprintf($msg, $plugin, $view));
    }

    public function sendContent() {
        // La closure qui exécute le template (sandbox)
        $render = function($viewPath, array $viewArgs = []) {
            extract($viewArgs, EXTR_SKIP);
            require $viewPath;
        };

        // Récupère le contexte
        $data = $this->viewArgs;

        // Binde la closure pour que $this soit dispo dans la vue
        if (isset($data['this'])) {
            $render = $render->bindTo($data['this'], $data['this']);
            unset($data['this']);
        }

        // Exécute le template
        $render($this->view, $data);

        return $this;
    }

    public function getContent() {
        return 'View: ' . $this->view . "\nData: " . var_export($this->viewArgs, true);
    }
}