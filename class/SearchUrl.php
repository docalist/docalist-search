<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2020 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Search;

use Docalist\Search\QueryParser\Parser;
use Docalist\Search\SearchRequest;
use Docalist\Search\Mapping\Field\Info\Features;
use InvalidArgumentException;
use WP_Rewrite;

/**
 * Gère une URL contenant des paramètres de recherche :
 * - permet de créer un objet SearchRequest à partir des arguments passés dans l'url
 * - permet de générer une nouvelle url en modifiant les paramètres de l'url :
 *      - activation/désactivation d'un filtre
 *      - changement de page
 *      - changement du nombre de notices par page
 *      - changement de l'ordre de tri
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class SearchUrl
{
    /**
     * Séparateur utilisé pour séparer les différentes valeurs dans les filtres multivalués.
     *
     * @var string
     */
    const SEPARATOR = ',';

    /**
     * Nom du paramètre "numéro de la page en cours" dans les paramètres de l'url.
     *
     * @var string
     */
    const PAGE = 'page';

    /**
     * Nom du paramètre "nombre de réponses par page" dans les paramètres de l'url.
     *
     * @var string
     */
    const SIZE = 'size';

    /**
     * Nom du paramètre "critères de tri" dans les paramètres de l'url.
     *
     * @var string
     */
    const SORT = 'sort';

    /**
     * Nom du paramètre "format du flux de syndication" dans les paramètres de l'url.
     *
     * @var string
     */
    const FEED = 'feed';

    /**
     * Url en cours.
     *
     * @var string
     */
    protected $url;

    /**
     * Liste des contenus sur lesquels portera la recherche.
     *
     * @var string[]
     */
    protected $types;

    /**
     * Url de base (i.e. $url sans la querystring)
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * Paramètres de l'url (version analysée de la query string)
     *
     * @var array
     */
    protected $parameters;

    /**
     * Requête de recherche construite à partir des paramètres de l'url.
     *
     * @var SearchRequest
     */
    protected $request;

    /**
     * Crée un nouvel objet SearchUrl
     *
     * @param string    $url    Url à analyser.
     * @param string[]  $types  Liste des types de contenus sur lesquels porte la recherche
     *                          (par défaut : tous les types indexés)
     */
    public function __construct($url = null, $types = [])
    {
        $this->setUrl($url);
        $this->types = $types;
    }

    /**
     * Retourne l'url en cours (l'url brute fournie à setUrl).
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Retourne la liste des types de contenus sur lesquels porte la recherche.
     *
     * @return string[]
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * Définit la liste des contenus sur lesquels porte la recherche.
     *
     * @param array $types
     *
     * @return self
     */
    public function setTypes(array $types = [])
    {
        $this->types = $types;

        return $this;
    }

    /**
     * Modifie l'url en cours.
     *
     * Remarque : toutes les autres propriétés de l'objet (baseUrl, parameters, request) sont réinitialisées.
     *
     * @param string $url
     *
     * @return self
     */
    public function setUrl($url)
    {
        $this->url = $url;

        $this->parameters = [];
        $pos = strpos($url, '?');
        if ($pos !== false) {
            $parameters = $this->parseQueryString(substr($url, $pos + 1));
            $this->setParameters($parameters);
            $url = substr($url, 0, $pos);
        }

        $this->setBaseUrl($url); // Important : après setParameters() car setBaseUrl() modifie le paramètre 'page'

        $this->request = null;

        return $this;
    }

    /**
     * Retourne l'url "propre" obtenue correspondant à l'url en cours
     *
     * - les paramètres vides sont supprimés
     * - les paramètres multivalués sont encodés avec une virgule.
     *
     * @return string
     */
    public function getCleanUrl()
    {
        return $this->buildUrl($this->parameters);
    }

    /**
     * Retourne le segment d'url utilisé par wordpress pour gérer la pagination ('/page/xxx' -> 'page').
     *
     * @return string|false
     */
    protected function getPaginationBase()
    {
        $wp_rewrite = docalist('wordpress-rewrite'); /* @var WP_Rewrite $wp_rewrite */

        return $wp_rewrite->using_permalinks() ? $wp_rewrite->pagination_base : false;
    }

    /**
     * Modifie l'url de base.
     *
     * La méthode gère les paginations propres à wordpress : si l'url est de la forme /page/xxx/, le paramètre 'page'
     * de l'url en cours est modifié et le segment correspondant est supprimé de l'url de base.
     *
     * @param string $url
     *
     * @return self
     */
    protected function setBaseUrl($url)
    {
        if ($pagination = $this->getPaginationBase()) {
            unset($this->parameters[self::PAGE]); // Si 'page' est déjà défini (query string), on ignore
            $match = [];
            if (preg_match("~$pagination/(\d+)/?$~", $url, $match)) {
                $page = (int) $match[1];
                $page > 1 && $this->parameters[self::PAGE] = $page;
                $url = substr($url, 0, -strlen($match[0]));
            }
        }
        $this->baseUrl = $url;

        return $this;
    }

    /**
     * Retourne l'url de base (i.e. l'url en cours sans la query-string éventuelle).
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Retourne un tableau contenant les paramètres de la recherche.
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Modifier les paramètres de la recherche.
     *
     * @param array $parameters
     *
     * @return self
     */
    public function setParameters(array $parameters)
    {
        // Dans la querystring, les paramètres ayant plusieurs valeurs peuvent être encodées de différentes façons :
        // 1. url générée depuis un formulaire php standard (exemple : recherche avancée)
        // - chaque champ multivalué est codé avec name="topic.filter[]"
        // - l'url générée est de la forme topic.filter[]=aaa&topic.filter[]=bbb
        // - soit après encodage : topic.filter%5B%5D=aaa&topic.filter%5B%5D=bbb
        //
        // 2. url générée via add_query_arg() ou via http_build_query()
        // - les clés des tableaux se retrouvent dans les urls.
        // - on obtient une url de la forme topic.filter[0]=aaa&topic.filter[1]=bbb
        // - soit après encodage : topic.filter%5B0%5D=aaa&topic.filter%5B1%5D=bbb
        //
        // 3. url générée via notre classe.
        // - pour rendre les urls un peu plus propres, on évite de générer des tableaux
        // - les valeurs multiples sont encodées dans un champ unique avec une virgule comme séparateur.
        // - par défaut, urlencode encode également la virgule alors que ce n'est pas requis.
        // - On utilise notre propre encodage pour éviter ça
        // - on obtient une url de la forme topic.filter=aaa,bbb

        // Transforme en tableau les filtres multivalués dont les valeurs sont séparées par des virgules
        $this->parameters = [];
        foreach ($parameters as $name => $value) {
            // Les paramètres "privés" de l'url (ceux qui ne concernent pas la recherche) sont stockés tels quels
            if ($name[0] === '_') {
                $this->parameters[$name] = $value;
                continue;
            }
            if (substr($name, 0, 10) === 'customize_') {
                continue;
            }

            if ($value === '') {
                continue;
            }

            if (is_string($value) && $this->isFilter($name)) {
                $value = explode(self::SEPARATOR, $value);
                $this->parameters[$name] = count($value) === 1 ? reset($value) : $value;
                continue;
            }

            $this->parameters[$name] = $value;
        }

        // Valide le paramètre "page"
        if (isset($this->parameters[self::PAGE])) {
            $page = filter_var($this->parameters[self::PAGE], FILTER_VALIDATE_INT);
            if ($page === false || $page <= 1) {
                unset($this->parameters[self::PAGE]);
            } else {
                $this->parameters[self::PAGE] = $page;
            }
        }

        // Valide le paramètre size
        if (isset($this->parameters[self::SIZE])) {
            $size = filter_var($this->parameters[self::SIZE], FILTER_VALIDATE_INT);
            if ($size === false || $size < 0) {
                unset($this->parameters[self::SIZE]);
            } else {
                $this->parameters[self::SIZE] = $size;
            }
        }

        // Done
        return $this;
    }

    /**
     * Encode un composant d'une URI.
     *
     * La méthode fonctionne comme la fonction encodeURIComponent() de javascript. Elle encode tous les caractères
     * sauf: A-Z a-z 0-9 - _ . ! ~ * ' ( )
     *
     * En interne, elle utilise la fonction php rawwurlencode(), puis décode les caractères ! * ' ( ).
     *
     * @param string $part
     *
     * @return string
     */
    private function encodeURIComponent(string $component): string
    {
        // source : https://stackoverflow.com/a/1734255
        $preserve = ['%21' => '!', '%2A' => '*', '%27' => "'", '%28' => '(', '%29' => ')'];

        return strtr(rawurlencode($component), $preserve);
    }

    /**
     * Version spéciale de parse_str() qui ne remplace pas les points et les espaces par des underscore.
     *
     * @author Rok Kralj
     * @see https://stackoverflow.com/a/18209799/1529493
     *
     * @author Kévin Dunglas
     * @see https://github.com/api-platform/core/blob/master/src/Util/RequestParser.php
     */
    protected function parseQueryString(string $source): array
    {
        // '[' is urlencoded ('%5B') in the input, but we must urldecode it in order
        // to find it when replacing names with the regexp below.
        $source = str_replace('%5B', '[', $source);
        $source = preg_replace_callback(
            '/(^|(?<=&))[^=[&]+/',
            function ($key) {
                return bin2hex(urldecode($key[0]));
            },
            $source
        );

        // parse_str urldecodes both keys and values in resulting array.
        $parameters = []; // avoid warning in eclipse
        parse_str($source, $parameters);

        return array_combine(array_map('hex2bin', array_keys($parameters)), $parameters);
    }

    /**
     * Construit une querystring à partir des paramètres indiqués.
     *
     * @param array $parameters
     *
     * @return string
     */
    protected function buildQueryString(array $parameters)
    {
        // "/" may appear unencoded as data within the query :
        // https://en.wikipedia.org/wiki/Uniform_Resource_Identifier#Syntax
        //
        // commas are explicitly allowed within query strings :
        // http://stackoverflow.com/a/2375597
        //
        // Le "~" n'est pas du tout réservé
        if (empty($parameters)) {
            return '';
        }
        $query = '' ;
        foreach ($parameters as $key => $value) {
            $query .= '&' . $this->encodeURIComponent($key);
            if ($value === '' || is_null($value)) {
                continue;
            }
            $query .= '=';
            if (is_array($value)) {
                foreach ($value as &$item) {
                    $item = $this->encodeURIComponent((string) $item);
                }
                $value = implode(self::SEPARATOR, $value);
            } else {
                $value = $this->encodeURIComponent((string) $value);
            }
            $query .= $value;
        }

        // Change le premier "&" généré en "?"
        $query[0] = '?';

        // Ok
        return $query;
    }

    /**
     * Construit une url de recherche avec les paramètres indiqués.
     *
     * @param array $parameters
     *
     * @return string
     */
    protected function buildUrl(array $parameters)
    {
        $url = $this->baseUrl;
        if (isset($parameters[self::PAGE]) && $pagination = $this->getPaginationBase()) {
            $url .= $pagination . '/' . $parameters[self::PAGE] . '/';
            unset($parameters[self::PAGE]);
        }

        return $url . $this->buildQueryString($parameters);
    }

    /**
     * Retourne un objet SearchRequest initialisé à partir des paramètres qui figure dans l'url.
     *
     * Remarque : l'objet SearchRequest est créé lors du premier appel, les appels successifs retournent le même objet.
     *
     * @return SearchRequest
     */
    public function getSearchRequest()
    {
        // Si la requête est déjà initialisée, on la retourne
        if ($this->request) {
            return $this->request;
        }

        // Sinon on initialise

        // Récupère le service DSL et le service QueryParser
        $dsl = docalist(QueryDSL::class);
        $parser = docalist(Parser::class); /* @var Parser $parser */

        // Par défaut, la requête portera sur tous les types qui ont été indiqués dans le constructeur
        // Si l'url contient des paramètres 'in', on les traduit en nom de CPT et cela restreint la liste
        $types = [];

        // Crée la requête. Les types interrogés seront définis plus tard.
        $this->request = new SearchRequest();

        // Initialise la requête à partir des arguments de l'url
        foreach ($this->parameters as $name => $value) {
            if ($name[0] === '_') {
                continue;
            }

            switch ($name) {
                case self::PAGE: // Numéro de la page en cours
                    $this->request->setPage($value);
                    break;

                case self::SIZE: // Nombre de réponses par page
                    if ($value !== SearchRequest::DEFAULT_SIZE) {
                        $this->request->setSize($value);
                    }
                    break;

                case self::SORT: // Critères de tri
                    $this->request->setSort($value);
                    break;

                case self::FEED: // Format du flux de syndication
                    // Rien à faire, on veut juste que ce ne soit pas ajouté à la requête de recherche
                    break;

                case 'in':
                    // 'in' contient des collections ('posts', 'pages', 'event'...) qu'il faut convertir en types (CPT)
                    // Si ce n'est pas une collection qu'on connait, on stocke tel quel (pseudo types comme "basket")
                    $collections = docalist(IndexManager::class)->getCollections();
                    foreach ((array) $value as $value) {
                        if (isset($collections[$value])) { // il s'agit d'une collection, convertit en nom de CPT
                            $value = $collections[$value]->getType();
                        }
                        $types[] = $value;
                    }
                    break;

                default:
                    // Teste s'il s'agit d'un filtre et récupère sa combinatoire
                    if ($op = $this->isFilter($name)) {
                        // Croisés en "et" : la requête doit matcher chacun des termes indiqués
                        if ($op === 'and') {
                            foreach ((array) $value as $value) {
                                $this->request->addFilter($dsl->term($name, $value));
                            }
                        }

                        // Croisés en "ou" : la requête doit matcher l'un des termes indiqués
                        else {
                            $this->request->addFilter($dsl->terms($name, (array) $value)/*, 'post-filter'*/);
                        }
                    }

                    // Champ
                    else {
                        $name === 'q' && $name = ''; // Requête "tous champs"
                        foreach ((array) $value as $value) {
                            $query = $parser->parse($value, $name);
                            $query && $this->request->addQuery($query);
                        }
                    }
            }
        }

        // Définit la liste des types interrogés
        $validTypes = $this->getTypes();
        if (!empty($types) && !empty($validTypes)) { // in ne peut pas être plus large que les types du constructeur
            $types = array_intersect($validTypes, $types);
        }
        $this->request->setTypes($types ?: $validTypes);

        // Définit la représentation sous forme d'équation de la requête
        $this->request->setEquation($this->getEquation());

        // Stocke la SearchUrl dans la requête
        $this->request->setSearchUrl($this);

        // Retourne le résultat
        return $this->request;
    }

    /**
     * Retourne une représentation de la requête sous forme d'équation de recherche.
     *
     * @return string
     */
    protected function getEquation()
    {
        // TODO: à améliorer. Quels paramètres prendre ? tout ? tout sauf les filtres ?
        $q = isset($this->parameters['q']) ? $this->parameters['q'] : '*';
        is_array($q) && $q = '(' . implode(') AND (', $q) . ')';

        return $q;
    }

    /**
     * Indique si un champ est un filtre et retourne sa combinatoire ('or' ou 'and').
     *
     * @param string $field
     *
     * @return false|'or'|'and' Retourne false si le champ n'est pas un filtre, sa combinatoire sinon.
     */
    protected function isFilter($field)
    {
        $searchAttributes = docalist(SearchAttributes::class); /** @var SearchAttributes $searchAttributes*/

        if ($searchAttributes->has($field, Features::FILTER | Features::EXCLUSIVE)) {
            return 'or';
        }

        if ($searchAttributes->has($field, Features::FILTER)) {
            return 'and';
        }

        return false;
    }

    /**
     * Indique si l'url contient le filtre indiqué.
     *
     * @param string $name Nom du filtre.
     * @param string $value Optionnel, valeur recherchée.
     *
     * @return bool
     */
    public function hasFilter($name, $value = null)
    {
        // Le filtre n'existe pas
        if (!isset($this->parameters[$name])) {
            return false;
        }

        // La valeur recherchée n'a pas été précisée
        if (is_null($value)) {
            return true;
        }

        // Le filtre ne contient qu'une seule valeur
        if (is_scalar($this->parameters[$name])) {
            return $this->parameters[$name] === $value;
        }

        // Teste si la valeur recherchée figure dans le filtre
        return in_array($value, $this->parameters[$name], true);
    }

    /**
     * Inverse un filtre.
     *
     * @param string $name Nom du filtre.
     * @param string $value Optionnel, valeur à inverser.
     *
     * @return string La nouvelle URL
     */
    public function toggleFilter($name, $value = null)
    {
        // Vérifie que le nom de champ indiqué est un filtre
//         if (! $this->isFilter($name) && !$this->isSpecialFilter($name)) {
//             throw new InvalidArgumentException("'$name' is not a filter");
//         }

        // Fait une copie des paramètres pour pouvoir les modifier
        $args = $this->parameters;

        // On a déjà des valeurs pour le filtre indiqué
        if (isset($args[$name])) {
            // On nous a demandé de supprimer toutes les valeurs existantes de ce filtre
            if (is_null($value)) {
                unset($args[$name]);
            }

            // On nous a demandé de supprimer une valeur particulière de ce filtre
            else {
                // Le filtre ne contient qu'une seule valeur
                if (is_scalar($args[$name])) {
                    // Si c'est celle qu'on nous a demandé de supprimer, on l'enlève
                    if ($args[$name] === $value) {
                        unset($args[$name]);
                    }

                    // Sinon, on transforme la valeur en tableau et on ajoute la nouvelle valeur
                    else {
                        $args[$name] = [$args[$name], $value];
                    }
                }

                // Le filtre contient déjà plusieurs valeurs
                else {
                    // Teste si la valeur à supprimer figure déja dans le filtre
                    $pos = array_search($value, $args[$name], true);

                    // La valeur existe déjà, on la supprime
                    if ($pos !== false) {
                        unset($args[$name][$pos]);
                        if (empty($args[$name])) {
                            unset($args[$name]);
                        } elseif (count($args[$name]) === 1) {
                            $args[$name] = reset($args[$name]);
                        }
                    }

                    // La valeur n'a pas été trouvée, on l'ajoute
                    else {
                        $args[$name][] = $value;
                    }
                }
            }
        }

        // On n'a pas encore de valeurs pour ce filtre, ajoute la valeur indiquée
        else {
            $args[$name] = $value;
        }

        // Réinitialise le numéro de page
        unset($args[self::PAGE]);

        // Construit l'url résultat
        return $this->buildUrl($args);
    }

    /**
     * Retourne l'url à utiliser pour afficher la page de résultat demandée.
     *
     * @param int $page
     *
     * @return string
     */
    public function getUrlForPage($page)
    {
        // Fait une copie des paramètres pour pouvoir les modifier
        $args = $this->parameters;

        // Vérifie qu'on nous a passé un numéro de page valide
        $page = filter_var($page, FILTER_VALIDATE_INT);
        if ($page === false || $page < 1) {
            throw new InvalidArgumentException('Incorrect page');
        }

        // Stocke le nouveau numéro de page si ce n'est pas la page par défaut
        if ($page <= 1) {
            unset($args[self::PAGE]);
        } else {
            $args[self::PAGE] = $page;
        }

        // Construit l'url résultat
        return $this->buildUrl($args);
    }

    /**
     * Retourne l'url à utiliser pour changer le nombre de réponses par page.
     *
     * Remarque : le paramètre 'page' de l'url retournée est également réinitialisé (première page).
     *
     * @param int $size
     *
     * @return string
     */
    public function getUrlForSize($size)
    {
        // Fait une copie des paramètres pour pouvoir les modifier
        $args = $this->parameters;

        // Vérifie qu'on nous a passé une taille valide
        $size = filter_var($size, FILTER_VALIDATE_INT);
        if ($size === false || $size < 0) {
            throw new InvalidArgumentException('Incorrect size');
        }

        // Stocke la nouvelle taille si ce n'est pas la taille par défaut
        if ($size === SearchRequest::DEFAULT_SIZE) {
            unset($args[self::SIZE]);
        } else {
            $args[self::SIZE] = $size;
        }

        // Réinitialise le numéro de page
        unset($args[self::PAGE]);

        // Construit l'url résultat
        return $this->buildUrl($args);
    }

    /**
     * Retourne l'url à utiliser pour changer l'ordre de tri des réponses.
     *
     * Remarque : le paramètre 'page' de l'url retournée est également réinitialisé (première page).
     *
     * @param string $sort
     *
     * @return string
     */
    public function getUrlForSort($sort)
    {
        // Fait une copie des paramètres pour pouvoir les modifier
        $args = $this->parameters;

        unset($args[self::SORT]);
        if ($sort !== '%' && $sort !== '_score') {
            // TODO : vérifier que le tri indiqué est valide
            // $clauses = apply_filters('docalist_search_get_sort', null, $sort);
            // $clauses && $args[self::SORT] = $sort;

            $args[self::SORT] = $sort;
        }

        // Réinitialise le numéro de page
        unset($args[self::PAGE]);

        // Construit l'url résultat
        return $this->buildUrl($args);
    }

    /*
     * mémo pour moi, exemples de clauses de tri retournées par le filtre "docalist_search_get_sort" :
     *
     * '%' : return '_score'
     * '+' : return '_doc'
     * '-' : return ['_doc' => ['order' => 'desc']]
     * 'creation+' : return ['creation' => ['order' => 'asc']] // asc = par défaut, inutile
     * 'creation-' : return ['creation' => ['order' => 'desc']]
     * 'ref+' : return ['ref' => ['order' => 'asc', 'missing' => '_last']]
     * 'createdby+' : return ['first-author' => ['order' => 'asc'], 'creation' => ['order' => 'desc']]
     */

    /**
     * Retourne l'url à utiliser pour générer un flux de syndication au format indiqué.
     *
     * @param string $format Format du flux de syndication à générer ('atom', 'rdf', 'rss' ou 'rss2').
     *
     * @return string
     */
    public function getUrlForFeed(string $format): string
    {
        // Fait une copie des paramètres pour pouvoir les modifier
        $args = $this->parameters;

        // Réinitialise le tri éventuel, c'est le flux qui fixe le tri (par date de création décroissante)
        unset($args[self::SORT]);

        // Réinitialise le numéro de page
        unset($args[self::PAGE]);

        // Ajoute un paramètre "feed" avec le format indiqué
        $args[self::FEED] = $format;

        // Construit l'url résultat
        return $this->buildUrl($args);
    }
}
