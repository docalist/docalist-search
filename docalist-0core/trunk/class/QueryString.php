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
		$parameters = array();

		if ($queryString) {
		    parse_str(ltrim($queryString, '?'), $parameters);
		}

		parent::__construct($parameters);
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
	 * Génère une chaine encodée contenant les paramètres actuels.
	 *
     * @param int $encoding Vous pouvez passer soit :
     *
     * - PHP_QUERY_RFC3986 : les espaces sont encodés sous la forme "%20".
     *   C'est la valeur par défaut. (cf http://www.faqs.org/rfcs/rfc3986).
     *
     * - PHP_QUERY_RFC1738 : les espaces sont encodés sous forme de "+"
     *   (cf http://www.faqs.org/rfcs/rfc1738).
	 *
	 * @return string La méthode retourne une chaine vide s'il n'y a aucun
	 * paramètre. Dans le cas contraire, la chaine obtenue commence par "?".
	 *
	 * Important : la chaine retournée est encodée mais elle n'est pas
	 * "escapée". Si vous insérez cette chaine dans un attribut html, vous
	 * devez appeller htmlspecialchars().
	 */
	public function encode($encoding = PHP_QUERY_RFC3986) {
	    if (empty($this->parameters)) {
    	    return '';
	    }

        return '?' . http_build_query($this->parameters, null, '&', $encoding);
	}
}