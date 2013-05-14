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
 * Permet de manipuler facilement une query-string (les paramètres d'une Uri).
 */
class QueryString extends Parameters {
	/**
	 * Construit une nouvelle query string.
	 *
	 * @param string $queryString une chaine encodée contenant les paramètres
	 * initiaux de la query string.
	 */
	public function __construct($queryString = null) {
		parent::__construct();

	    /**
	     * Remarque : un espace peut être encodé soit par un "+" soit par "%20".
	     * Pour décoder la query string, on utilise urldecode(), qui teste les
	     * deux plutôt que rawaurldecode() qui ne décode que %20.
	     */
		if ($queryString) {
		    foreach (explode('&', $queryString) as $arg) {
		        if (false === $pt = strpos($arg, '=')) {
		            $key = urldecode($arg);
		            $value = null;
		        } else {
		            $key = urldecode(substr($arg, 0, $pt));
		            $value = urldecode(substr($arg, $pt + 1));
		        }
		        $this->add($key, $value);
		    }
		}
	}

	/**
	 * Méthode statique permettant de créer un nouvel objet QueryString à
	 * partir des paramètres qui figurent dans l'url en cours.
	 *
	 * @return self $this
	 */
	public static function fromCurrent() {
	    $url = $_SERVER['REQUEST_URI'];

        if (false !== $pt = strpos($url, '?')) {
            return new self(substr($url, $pt + 1));
        }

        return new self();
    }

    /**
     * Génère une query string à partir des paramètres actuels.
     *
     * @return string La méthode retourne une chaine vide s'il n'y a aucun
     * paramètre. Dans le cas contraire, la chaine obtenue commence par "?".
     *
     * Important : la chaine retournée est encodée avec rawurlencode() mais
     * elle n'est pas "escapée". Si vous insérez cette chaine dans un attribut
     * html, vous devez appeller htmlspecialchars().
     */
    public function encode() {
        if (empty($this->parameters)) {
            return '';
        }

        $query = '' ;
        foreach($this->parameters as $key => $value) {
            if (is_null($value)) {
                $query .= '&' . rawurlencode($key);
            } else {
                foreach((array) $value as $value) {
                    $query .= '&' . rawurlencode($key) . '=' . rawurlencode($value);
                }
            }
        }

        $query[0] = '?';

        return $query;
    }
}