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

/**
 * Une redirection
 */
class RedirectResponse extends HtmlResponse {

    public function __construct($url, $status = 302, $headers = array()) {
        $headers['Location'] = $url;
        parent::__construct(null, $status, $headers);
    }

    public function setContent($content) {
        if (empty($content)) {
            $url = htmlspecialchars($this->headers->get('Location'), ENT_QUOTES, 'UTF-8');

            $content = "<!DOCTYPE html>
                <html>
                    <head>
                        <meta http-equiv='refresh' content='0;url=$url' />
                        <script type='text/javascript'>window.location='$url';</script>
                        <title>This page has moved</title>
                    </head>
                    <body>
                        <h1>$this->statusCode - $this->statusText</h1>
                        <p>This page has moved to <a href='$url'>$url</a>.</p>
                    </body>
                </html>";
        }

        return parent::setContent($content);
    }
}