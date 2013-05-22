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
use Exception;

/**
 * Permet de manipuler facilement une Url.
 */
class Uri extends QueryString {
    protected $parts;

    /**
     * Construit une nouvel objet Uri.
     *
     * @param string $url l'url.
     */
    public function __construct($url = null) {
        if ($url) {
            $this->parts = parse_url($url);
            if ($this->parts === false) {
                throw new Exception('Url incorrecte');
            }

            if (isset($this->parts['query'])) {
                parent::__construct($this->parts['query']);
                unset($this->parts['query']);
            }
            else {
                parent::__construct();
            }
        }
    }

    /**
     * Méthode statique permettant de créer un nouvel objet Uri à
     * partir de l'url en cours.
     *
     * @return self $this
     */
    public static function fromCurrent() {
        return new self($_SERVER['REQUEST_URI']);
    }

    /**
     * Génère une url à partir des paramètres actuels.
     *
     * @return string
     */
    public function encode() {
        $url = '';

        if (isset($this->parts['scheme'])) {
            $url .= $this->parts['scheme'] . '://';
        }

        if (isset($this->parts['user'])) {
            $url .= $this->parts['user'];
        }
        if (isset($this->parts['pass'])) {
            $url .= ':' . $this->parts['pass'];
        }
        if (isset($this->parts['user']) || isset($this->parts['pass'])) {
            $url .= '@';
        }

        if (isset($this->parts['host'])) {
            $url .= $this->parts['host'];
        }

        if (isset($this->parts['port'])) {
            $url .= ':' . $this->parts['port'];
        }

        if (isset($this->parts['path'])) {
            $url .= $this->parts['path'];
        }

        if ($this->parameters) {
            $url .= parent::encode();
        }

        if (isset($this->parts['fragment'])) {
            $url .= '#' . $this->parts['fragment'];
        }

        return $url;
    }
}