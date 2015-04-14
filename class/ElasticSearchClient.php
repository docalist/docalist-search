<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
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
     * Les paramètres du serveur.
     *
     * @var ServerSettings $settings
     */
    protected $settings;

    /**
     * Handle curl utilisé pour les requêtes synchrones.
     *
     * @var resource
     */
    protected $curl;

    /**
     * Construit un nouveau client ElasticSearch en utilisant les options de
     * configuration passées en paramètre.
     *
     * @param ServerSettings $settings Les paramètres du serveur.
     */
    public function __construct(ServerSettings $settings) {
        $debug = false;

        $this->settings = $settings;

        if ($debug) {
            add_action('elastic-search-request', function($request, $curl) {
                $headers = curl_getinfo($curl, CURLINFO_HEADER_OUT);
                echo '<pre style="background-color:#DDF2FF">';
                echo rtrim($headers, "\r\n");
                if ($request) {
                    echo "\n", $this->prettify($request);
                }
                echo '</pre>';
            }, 10, 2);

            add_action('elastic-search-response', function($response, $curl) {
                $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
                $header = substr($response, 0, $headerSize);
                $response = substr($response, $headerSize);

                $time = curl_getinfo($curl, CURLINFO_TOTAL_TIME) * 1000;

                echo '<pre style="background-color:#E8FFDD">';
                echo $time, ' ms - ', $header;
                if ($response) {
                    echo ' ', $this->prettify($response);
                }
                echo '</pre>';

                // echo '<pre style="background-color:#FFD67D"><b>Curl Info: </b>';
                // print_r(curl_getinfo($this->curl));
                // echo '</pre>';
            }, 10, 2);
        }
    }

    /**
     * Destructeur. Ferme les ressources curl utilisées.
     */
    public function __destruct() {
        ! is_null($this->curl) && curl_close($this->curl);
    }

    /**
     * Essaie de reformatter et d'indenter le code JSON passé en paramètre.
     *
     * @param string $data Les données à indenter.
     *
     * @return string Si la chaine passée en paramètre est du JSON valide, la
     * méthode la décode puis la recode avec l'option JSON_PRETTY_PRINT, sinon
     * elle retourne la chaine originale.
     */
    protected function prettify($data) {
        $decoded = json_decode($data, false, 512, JSON_BIGINT_AS_STRING);
        if (json_last_error()) {
            return $data;
        }

        return json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Prépare une requête et retourne les options CURL à utiliser.
     *
     * @param string $method La méthode HTTP à employer (HEAD, GET, POST...)
     * @param string $path Le path de l'url du endpoint ElasticSearch à appeler.
     * @param string $data En entrée, données à envoyer au serveur, en sortie,
     * code source JSON effectivement envoyé.
     * @param string $timeout Optionnel, timeout de la requête, en secondes. Si
     * aucun timeout n'est fourni, la méthode utilise le timeout par défaut
     * définit dans les paramètres du serveur.
     *
     * @return array Les options à passer à curl_setopt_array().
     *
     * @throws Exception
     */
    protected function prepareRequest($method, $path = null, & $data = null, $timeout = null) {
        // Sanity check
        if ($path === '' || $path[0] != '/') {
            throw new Exception('Invalid url path, do not start with "/" ' . $path);
        }

        // Construit l'url
        $path = str_replace('{index}', $this->settings->index(), $path);
        $url = $this->settings->url() . $path;

        // Calcule le timeout (en ms) à utiliser pour cette requête
        $connect = $this->settings->connecttimeout();
        is_null($timeout) && $timeout = $this->settings->timeout();

        // Construit le corps de la requête
        $headers = [];

        // La majorité des actions ES attendent un objet encodé en json
        if (! is_string($data) && ! is_null($data)) {
            $data = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($data === false) {
                throw new Exception('Json_encode error');
            }

            $headers[] = 'Content-Type: application/json; charset=UTF-8';
        }

        // Mais certaines actions attendent une chaine (_analyze par exemple)
        else {
            $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        }

        // Compresse le corps de la requête
        $body = $data;
        if ($data && $this->settings->compressrequest()) {
            // gzip : RFC 1952 -> il faut utiliser gzencode / gzdecode
            if (false === $body = gzencode($data, 6)) {
                throw new Exception('Error while gzipping request body');
            }
            $headers[] = 'Content-Encoding: gzip';
        }

        // Détermine les options de la requête CURL
        $options = [
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,   // Version http

            CURLOPT_SSL_VERIFYPEER  => false,    // Ne pas vérifier le certificat SSL

            CURLOPT_CUSTOMREQUEST   => $method,  // Méthode HTTP à utiliser
            CURLOPT_URL             => $url,     // URL de la requête
            CURLOPT_HTTPHEADER      => $headers, // Entêtes HTTP de la requête
            CURLOPT_POSTFIELDS      => $body,    // Corps de la requête

            CURLOPT_CONNECTTIMEOUT  => $connect, // Timeout pour la connexion (ms)
            CURLOPT_TIMEOUT         => $timeout, // Timeout pour la requête (ms)

            CURLOPT_HEADER          => true,     // Inclure les entêtes http dans la réponse
            CURLOPT_RETURNTRANSFER  => true,     // Retourner la réponse, ne pas l'afficher

            CURLINFO_HEADER_OUT     => true,     // Pour les logs : récupérer les entêtes de la requête
        ];

        // Propose au serveur de compresser sa réponse si l'option est activée
        $this->settings->compressresponse() && $options[CURLOPT_ENCODING] = '';

        return $options;
    }

    /**
     * Décode la réponse fournit par le serveur.
     *
     * Génère une exception si la requête n'a pas abouti, décode la réponse si
     * elle est au format JSON.
     *
     * @param resource $curl La ressource curl utilisée pour la requête.
     *
     * @param string $response La réponse à décoder.
     *
     * @return string|object Retourne un objet si la réponse est au format JSON,
     * la réponse obtenue sinon.
     *
     * @throws Exception Si response vaut false.
     */
    protected function parseResponse($curl, $response) {
        // Teste si une erreur s'est produite
        if ($response === false) {
            throw new Exception('ElasticSearch error: ' . curl_error($curl));
        }

        // Ignore les entêtes de la réponse
        $response = substr($response, curl_getinfo($curl, CURLINFO_HEADER_SIZE));

        // Si la réponse est en JSON, on la décode
        if (curl_getinfo($curl, CURLINFO_CONTENT_TYPE) === 'application/json; charset=UTF-8') {
            if (is_null($response = json_decode($response, false, 512, JSON_BIGINT_AS_STRING))) {
                throw new Exception('Error while decoding JSON response');
            }
        }

        return $response;
    }

    /**
     * Exécute une requête ElasticSearch.
     *
     * @param string $method La méthode HTTP à employer (HEAD, GET, POST...)
     * @param string $path Le path de l'url du endpoint ElasticSearch à appeler.
     * @param mixed $data Les données à envoyer au serveur. Si vous passez une
     * chaine de caractère, celle-ci est envoyée telle quelle, sinon les données
     * sont encodées en JSON.
     * @param string $timeout Optionnel, timeout de la requête, en secondes. Si
     * aucun timeout n'est fourni, la méthode utilise le timeout par défaut
     * définit dans les paramètres du serveur.
     *
     * @return string|object Retourne un objet si la réponse du serveur est au
     * format JSON, une chaine contenant la réponse obtenue sinon.
     */
    protected function request($method, $path = null, $data = null, $timeout = null) {
        // Prépare la requête
        $options = $this->prepareRequest($method, $path, $data, $timeout);
        is_null($this->curl) && $this->curl = curl_init();

        // Exécute la requête
        curl_setopt_array($this->curl, $options);
        $response = curl_exec($this->curl);

        // Loggue la requête exécutée et la réponse obtenue
        do_action('elastic-search-request', $data, $this->curl);
        do_action('elastic-search-response', $response, $this->curl);

        // Décode la réponse
        return $this->parseResponse($this->curl, $response);
    }


    /**
     * Retourne le temps d'exécution de la dernière requête exécutée.
     *
     * @return int Durée en secondes de la dernière transaction ou -1 si aucune
     * requête n'a encore été exécutée.
     */
    public function time() {
        return $this->curl ? curl_getinfo($this->curl, CURLINFO_TOTAL_TIME) : -1;
    }

    /**
     * Exécute une requête HEAD.
     *
     * @param string $path
     * @param mixed $data
     * @param string $timeout
     *
     * @return string|object
     */
    public function head($path = null, $data = null, $timeout = null) {
        return $this->request('HEAD', $path, $data, $timeout);
    }

    /**
     * Exécute une requête GET.
     *
     * @param string $path
     * @param mixed $data
     * @param string $timeout
     *
     * @return string|object
     */
    public function get($path = null, $data = null, $timeout = null) {
        return $this->request('GET', $path, $data, $timeout);
    }

    /**
     * Exécute une requête POST.
     *
     * @param string $path
     * @param mixed $data
     * @param string $timeout
     *
     * @return string|object
     */
    public function post($path = null, $data = null, $timeout = null) {
        return $this->request('POST', $path, $data, $timeout);
    }

    /**
     * Exécute une requête PUT.
     *
     * @param string $path
     * @param mixed $data
     * @param string $timeout
     *
     * @return string|object
     */
    public function put($path = null, $data = null, $timeout = null) {
        return $this->request('PUT', $path, $data, $timeout);
    }

    /**
     * Exécute une requête DELETE.
     *
     * @param string $path
     * @param mixed $data
     * @param string $timeout
     *
     * @return string|object
     */
    public function delete($path = null, $data = null, $timeout = null) {
        return $this->request('DELETE', $path, $data, $timeout);
    }

    // Remarque : les autres verbes http (TRACE, OPTIONS...) ne sont pas utilisés par ElasticSearch.

    /**
     * Teste l'existence d'une ressource.
     *
     * Exécute une requête HEAD et teste si le serveur retourne un statut 2200.
     *
     * @param string $path
     * @param mixed $data
     * @param string $timeout
     *
     * @return string|object
     */
    public function exists($path = null, $data = null, $timeout = null) {
        $this->request('HEAD', $path, $data, $timeout);
        return curl_getinfo($this->curl, CURLINFO_HTTP_CODE) === 200;
    }

    /**
     * Exécute une requête ElasticSearch de type BULK.
     *
     * La requête exécutée est de type PUT. Les données doivent correspondre au
     * format bulk attendu par ES.
     *
     * @param string $path
     * @param mixed $data
     * @param string $timeout
     *
     * @return string|object
     */
    public function bulk($path = null, $data = null, $timeout = null) {
        return $this->request('PUT', $path, $data, $timeout);
    }
}