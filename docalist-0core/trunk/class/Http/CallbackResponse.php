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

class CallbackResponse extends Response {
    protected $defaultHeaders = [
        'Content-Type' => 'text/html; charset=UTF-8',
    ];

    protected $callback;

    public function __construct($callback = null, $status = 200, $headers = array()) {
        parent::__construct(null, $status, $headers);

        $this->callback = $callback;
    }

    public function sendContent() {
        call_user_func($this->callback);
    }

    public function getContent() {
        is_callable($this->callback, true, $name);

        return 'Callback: ' . $name;
    }
}
