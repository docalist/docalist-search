<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2012, 2013 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */
namespace Docalist\Search;
use Exception;

/**
 * Classe client pour se connecter à un serveur ElasticSearch.
 */
class ElasticSearchClient {
    /**
     * @var string Url du serveur ElasticSearch.
     */
    protected $server;

    /**
     * @var string Nom de l'index ElasticSearch dans lequel on va indexer
     * nos documents.
     */
    protected $index;

    /**
     * @var int Timeout des requêtes.
     */
    protected $timeout;

    /**
     * @var resource Le contexte retourné par stream_context_create().
     */
    protected $context;

    /**
     * @var array Entêtes de la réponse reçue lors de la dernière requête
     * (copie de $http_response_header).
     */
    protected $response;

    /**
     * @var float Durée de la dernière requête exécutée.
     */
    protected $time;

    /**
     * Construit un nouveau client ElasticSearch en utilisant les options de
     * configuration passées en paramètre.
     *
     * @param ServerSettings $settings Les paramètres du serveur.
     */
    public function __construct(ServerSettings $settings) {
        $this->configure($settings);
    }

    /**
     * Configure le client ElasticSearch.
     *
     * Cette méthode est utile quand la config change (exemple : indexer).
     *
     */
    public function configure(ServerSettings $settings) {
        $this->server = $settings->url();
        $this->index = $settings->index();
        $this->timeout = $settings->timeout();

        $this->context = stream_context_create(array(
            // cf http://www.php.net/manual/en/context.http.php
            'http' => array(
                'method' => 'GET',
                'user-agent' => 'docalist-search',
                'timeout' => $this->timeout,
                // 'protocol_version' => 1.1,
                'ignore_errors' => true,

            ),
        ));
        // todo https ?
        // todo : ajouter host:server dans les headers. Peut être nécessaire pour certains proxys
        // cf http://www.php.net/manual/fr/function.file-get-contents.php#106969
    }

    /**
     * Retourne le status code http retourné par la dernière requête.
     *
     * @return int
     */
    public function statusCode() {
        if (isset($this->response) && isset($this->response[0])){
            return (int) substr($this->response[0], 9, 3);
        }

        return 0;
    }

    public function time() {
        return $this->time;
    }

    /**
     * Par défaut, le path est relatif à l'index
     * path absolu (commençant par slash) : appliqué au server :
     * http://localhost:9200$PATH
     *
     * path relatif (pas de slash initial) : appliqué à l'index
     * http://localhost:9200/$index/$PATH
     */
    public function request($method, $path = null, $data = null) {
        $debug = false;
        $pretty = $debug ? JSON_PRETTY_PRINT : 0;

        if ($path && $path[0] === '/') {
            $url = $this->server . ltrim($path, '/');
        }
        else {
            $url = $this->server . $this->index . '/' . $path;
        }

        if ($debug) {
            $url .= strpos($path, '?') === false ? '?pretty' : '&pretty';
        }

        if ($debug) {
            echo '<pre style="width; auto;color: #006; background: #C0DAA4; border-radius: 10px;padding: 1em; box-shadow: 5px 5px 20px #647F47;">';
            echo "curl -D- -X$method '$url'";
        }

        // Définit la méthode http à utiliser
        stream_context_set_option($this->context, 'http', 'method', $method);

        // Données de la requête (request body)
        if (! is_null($data)) {
            if (! is_string($data)) {
                $data = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | $pretty);
                if ($data === false) {
                    throw new Exception('json_encode error');
                }
            }
            if ($debug) {
                echo " -d '\n",htmlspecialchars($data),"\n'";
            }

            // Fournit le body de la requête
            stream_context_set_option($this->context, 'http', 'content', $data);

            // On n'a plus besoin de data, libère la mémoire (exemple bulk)
            unset($data);
        }

        if ($debug) {
            echo "</pre>";
        }

        $start = microtime(true);
        $data = @file_get_contents($url, false, $this->context);
        // On n'a plus besoin du body, libère la mémoire (exemple bulk)
        stream_context_set_option($this->context, 'http', 'content', null);
        $this->time = (int) ((microtime(true) - $start) * 1000);

        if ($data === false) {
            throw new Exception('ElasticSearch: the server does not respond.');
        }
        $this->response = $http_response_header;

        if ($debug) {
            echo '<pre style="width: auto;font-weight: bold; color: #060; background: #CCC; border-radius: 10px;padding: 1em; box-shadow: 5px 5px 20px #647F47;">';
            echo implode("\n", $this->response), "\n";
            if ($data != '') echo "\n", $data, "\n";
            echo '</pre>';
        }

        $statusCode = $this->statusCode();
        switch ($statusCode) {
            case 200: // OK
            case 201: // Created
            case 404:
                if ($data ==='') {
                    return $statusCode;
                }

                $data = json_decode($data);
                if (is_null($data)) {
                    throw new Exception('json_decode error');
                }

                return $data;

            default :
                throw new Exception("ElasticSearch: $data");
        }
    }

    public function head($path = null, $data = null) {
        return $this->request('HEAD', $path, $data);
    }

    public function get($path = null, $data = null) {
        return $this->request('GET', $path, $data);
    }

    public function post($path = null, $data = null) {
        return $this->request('POST', $path, $data);
    }

    public function put($path = null, $data = null) {
        return $this->request('PUT', $path, $data);
    }

    public function delete($path = null, $data = null) {
        return $this->request('DELETE', $path, $data);
    }

    // Remarque : le verbe http OPTIONS n'est pas utilisé par ElasticSearch.

    public function exists($path = null, $data = null) {
        $this->request('HEAD', $path, $data);
        return $this->statusCode() === 200;
    }

    public function bulk($path = null, $data = null) {
        return $this->request('PUT', $path, $data);
    }

}
