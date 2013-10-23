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

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * La classe Response représente le résultat de l'exécution d'une requête http.
 *
 * L'implémentation actuelle est basée sur l'objet Response du composant
 * Symfony HttpFoundation.
 */
class Response extends SymfonyResponse {
    /**
     * Entête http par défaut pour ce type de réponse.
     *
     * @var string[]
     */
    protected $defaultHeaders;

    public function __construct($content = '', $status = 200, $headers = array()) {
        $this->defaultHeaders && $headers += $this->defaultHeaders;

        parent::__construct($content, $status, $headers);
    }

    public function prepare(SymfonyRequest $request = null) {
        return parent::prepare($request ?: Request::createFromGlobals());
    }
}