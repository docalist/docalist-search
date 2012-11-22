<?php
/**
 * This file is part of the "Docalist Core" plugin.
 *
 * Copyright (C) 2012 Daniel Ménard
 * 
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */

namespace Docalist\Core;
use ReflectionObject, Exception;

/**
 * Classe de base abstraite représentant un outil.
 */
abstract class AbstractTool {
    /**
     * @var boolean Indique si l'outil s'exécute en ajax (dans un frame
     * thickbox) ou comme une page normale.
     */
    protected $ajax = false;

    /**
     * @var string Arguments supplémentaires à inclure dans l'url de l'outil.
     *
     * Cette propriété est utile pour passer des paramètres à l'outil mais
     * également, dans le cas d'un outil ajax, pour passer des paramètres à
     * thickbox (par exemple : height=85&width=250&modal=true).
     */
    protected $extraArguments = '';

    /**
     * Construit une nouvelle instance de l'outil.
     *
     * @param null|array $args un tableau contenant les paramètres de
     * l'outil.
     *
     * @throws Exception Si le tableau contient des paramètres non reconnus
     * (i.e. qui ne sont pas des propriétés de l'outil.)
     */
    public function __construct($args = array()) {
        if (empty($args)) {
            return;
        }

        foreach ($args as $property => $value) {
            if (!property_exists($this, $property)) {
                $message = __('Propriété invalide %s', 'docalist-core');
                throw new Exception(sprintf($message, $property));
            }
            $this->$property = $value;
        }
    }


    /**
     * Retourne le nom de l'outil (son libellé).
     *
     * @return string La méthode par défaut retourne le nom de la classe.
     *
     * Les classes descendantes peuventu surcharger cette méthode pour
     * retourner un libellé un plus compréhensif.
     */
    public function name() {
        return get_class($this);
    }


    /**
     * Retourne la description de l'outil.
     *
     * @return string La méthode par défaut retourne une chaine vide.
     */
    public function description() {
        return '';
    }


    /**
     * Indique si l'outil s'exécute en ajax (dans un frame thickbox) ou
     * comme une page normale.
     *
     * @return boolean
     */
    public function ajax() {
        return $this->ajax;
    }


    /*
     * Retourne les arguments supplémentaires à inclure dans l'url de l'outil.
     *
     * Cette propriété est utile pour passer des paramètres à l'outil mais
     * également, dans le cas d'un outil ajax, pour passer des paramètres à
     * thickbox (par exemple : height=85&width=250&modal=true).
     */
    public function extraArguments() {
        return $this->extraArguments;
    }


    /**
     * Point d'entrée de l'outil : lance l'exécution.
     *
     * @return null|boolean
     */
    abstract public function actionIndex();

    /**
     * Lance l'exécution de l'outil.
     *
     * @return null|boolean
     */
    public final function run() {
        // Détermine la mathode à appeller
        $name = isset($_GET['m']) ? $_GET['m'] : 'index';
        $name = 'action' . $name;

        // Vérifie que la méthode demandée existe
        $class = new ReflectionObject($this);
        if (!$class->hasMethod($name)) {
            echo "404 Not Found : method $name do not exist";
            return false;
        }

        // On va construire un tableau args contenant tous les paramètres
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
                echo "400 Bad request : $name is required";
                return false;
            }

            // Ok
            $args[$name] = $param->getDefaultValue();
        }

        // Appelle la méthode avec la liste d'arguments obtenus
        return $method->invokeArgs($this, $args);
    }


    /**
     * Retourne l'url à utiliser pour appeller une action dans de l'outil.
     * 
     * Exemples d'utilisation :
     * - url de l'action en cours : 
     *   <code>url()</code>
     * 
     * - url de l'action en cours, en lui passant un paramètre :
     *   <code>url(array('arg'=>'value'))</code>
     *
     * - url pour appeller la méthode actionTest() sans paramètres :
     *   <code>url('test')</code>
     * 
     * - url pour appeller la méthode actionTest() en lui passant un paramètre :
     *   <code>url('test', array('arg'=>'value'))</code>
     * 
     * - url pour appeller la méthode actionOther() d'un autre outil :
     *   <code>url('other', array('t'=>'An.Other.Tool', 'arg' => 'value'))</code>
     *
     * @param string $method (optionnel) nom de la méthode à appeller.
     * @param array $params (optionnel) paramètres à passer à la méthode.
     * 
     * @todo En mode debug, vérifier que les arguments requis de la méthode 
     * appelée sont bien présents dans les paramètres, générer une exception 
     * sinon. Vérifier également que la méthode appellée existe et est 
     * publique. Ces tests sont faits par run() mais on ne détecte les erreurs
     * que dans les liens qu'on clique. En le faisant également içi, cela
     * permettrait d'être sur que tous les liens qu'on génère sont valides (en
     * cas d'erreur, on le verrait immédiatement).
     * @todo A faire également : ajouter un 3ème paramètre "conserver les 
     * paramètres actuels".
     */
    protected function url($method = null, $params = null) {
        // Paramètres indispensables qu'on va ensuite merger avec $params
        $args = array();
        $ajax= defined('DOING_AJAX');

        // Teste si c'est un appel sans nom de méthode : url() ou url(params)
        if (is_array($method)) {
            $params = $method;
            $method = null;
        }

        // Si method est null, on prend la méthode en cours ("m", sinon index)
        if (is_null($method) && isset($_GET['m']))
            $method = $_GET['m'];

        // En ajax, l'url est de la forme admin-ajax.php?action=docalist-tools
        if ($ajax && isset($_GET['action']))
            $args['action'] = $_GET['action'];

        // En mode normal, l'url est de la forme tools.php?page=docalist-tools
        if (!$ajax && isset($_GET['page']))
            $args['page'] = $_GET['page'];

        // Le nom de l'outil. $params prioritaire pour les liens inter-tools
        if (isset($_GET['t']) && !isset($params['t']))
            $args['t'] = $_GET['t'];

        // Nom de la méthode à appeller
        if ($method)
            $args['m'] = $method;

        // Paramètres de la méthode
        if ($params)
            $args += $params;

        // Construit la query string
        $query = http_build_query($args, '', '&');
        
        // Détermine l'url de base
        if ($ajax)
            $base = admin_url('admin-ajax.php?action=docalist-tools');
        else
            $base = menu_page_url('docalist-tools', false);
        
        // Retourne l'url complète
        return $base . '&' . $query;
    }


}
