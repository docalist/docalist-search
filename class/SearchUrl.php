<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2013-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search;

use Docalist\Search\QueryParser\Parser;
use Docalist\Search\SearchRequest2 as SearchRequest;
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
    public function __construct($url = null, $types = null)
    {
        $this->setUrl($url);
        if (empty($types)) {
            $indexManager = docalist('docalist-search-index-manager'); /** @var IndexManager $indexManager */
            $types = $indexManager->getTypes();
        }
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
     */
    public function setUrl($url)
    {
        $this->url = $url;

        $this->parameters = [];
        $pos = strpos($url, '?');
        if ($pos !== false) {
            $parameters = [];
            parse_str(substr($url, $pos + 1), $parameters);
            $this->setParameters($parameters);
            $url = substr($url, 0, $pos);
        }

        $this->setBaseUrl($url); // Important : après setParameters() car setBaseUrl() modifie le paramètre 'page'

        $this->request = null;
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
        global $wp_rewrite; /** @var WP_Rewrite $wp_rewrite */

        return $wp_rewrite->using_permalinks() ? $wp_rewrite->pagination_base : false;
    }

    /**
     * Modifie l'url de base.
     *
     * La méthode gère les paginations propres à wordpress : si l'url est de la forme /page/xxx/, le paramètre 'page'
     * de l'url en cours est modifié et le segment correspondant est supprimé de l'url de base.
     *
     * @param string $url
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
    }

    /**
     * Retourne l'url de base (i.e. l'url en cours sans la query-string éventuelle).
     *
     * @eturn string
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
        foreach($parameters as $name => $value) {
            // Les paramètres "privés" de l'url (ceux qui ne concernent pas la recherche) sont stockés tels quels
            if ($name[0] === '_') {
                $this->parameters[$name] = $value;
                continue;
            }

            if ($value === '') {
                continue;
            }

            // parse_url convertit les "." en "_"
            $name = strtr($name, '_', '.');

            if (is_string($value) && ($this->isFilter($name) || $this->isSpecialFilter($name))) {
                $value = explode(self::SEPARATOR, $value);
                $this->parameters[$name] = count($value) === 1 ? reset($value) : $value;
                continue;
            }

            $this->parameters[$name] = $value;
        }

        // Valide le paramètre "page"
        if (isset($this->parameters[self::PAGE])) {
            $page = filter_var($this->parameters[self::PAGE], FILTER_VALIDATE_INT);
            if ($page === false || $page <= 1 /* || $page === SearchRequest::DEFAULT_PAGE */) {
                unset($this->parameters[self::PAGE]);
            } else {
                $this->parameters[self::PAGE] = $page;
            }
        }

        // Valide le paramètre size
        if (isset($this->parameters[self::SIZE])) {
            $size = filter_var($this->parameters[self::SIZE], FILTER_VALIDATE_INT);
            if ($size === false || $size < 0 || $size === SearchRequest::DEFAULT_SIZE) { // 0 = no limit
                unset($this->parameters[self::SIZE]);
            } else {
                $this->parameters[self::SIZE] = $size;
            }
        }

        // Done
        return $this;
    }

    /**
     * Construit une querystring à partir des paramètres indiqués.
     *
     * @param array $parameters
     *
     * @return string
     */
    protected function buildQueryString(array $parameters) {
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
        foreach($parameters as $key => $value) {
            $query .= '&' . rawurlencode($key);
            if ($value === '' || is_null($value)) {
                continue;
            }
            $query .= '=';
            if (is_array($value)) {
                foreach($value as &$item) {
                    $item = rawurlencode($item);
                }
                $value = implode(self::SEPARATOR, $value);
            } else {
                $value = rawurlencode($value);
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
     * Retourne la liste des collections indexées.
     *
     * @return string[] Un tableau de la forme collection (in) => type
     */
    protected function getCollections()
    {
        $indexManager = docalist('docalist-search-index-manager'); /** @var IndexManager $indexManager */
        $collections = [];
        foreach($indexManager->getTypes() as $type) {
            $indexer = $indexManager->getIndexer($type);
            $collection = $indexer->getCollection();
            $collections[$collection] = $type;
        }

        return $collections;
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
        $dsl = docalist('elasticsearch-query-dsl'); /** @var QueryDSL $dsl */
        $parser = docalist('query-parser'); /** @var Parser $parser */

        // Par défaut, la requête portera sur tous les types qui ont été indiqués dans le constructeur
        // Si l'url contient des paramètres 'in', cela restreint la liste
        $in = [];

        // Crée la requête. Les types interrogés seront définis plus tard.
        $this->request = new SearchRequest();

        // Initialise la requête à partir des arguments de l'url
        foreach($this->parameters as $name => $value) {
            if ($name[0] === '_') {
                continue;
            }

            switch($name) {
                case self::PAGE: // Numéro de la page en cours
                    $this->request->setPage($value);
                    break;

                case self::SIZE: // Nombre de réponses par page
                    $this->request->setSize($value);
                    break;

                case self::SORT: // Critères de tri
                    $sortClause = []; // TODO : getSortClause($value);
                    $this->request->setSort($sortClause);
                    break;

                case 'in':
                    // 'in' contient des collections ('posts', 'pages', 'event'...) qu'il faut convertir en types (CPT)
                    $collections = $this->getCollections();
                    foreach ((array) $value as $value) {
                        if (!isset($collections[$value])) {
                            // echo "WARNING: la collection'$value' indiquée dans 'in' n'existe pas, ignorée<br />";
                            // ignore en silence
                            continue;
                        }
                        $in[] = $collections[$value];
                    }
                    break;

                default:
                    // Filtre standard
                    if ($this->isFilter($name)) {
                        // Croisés en "et" : la requête doit matcher chacun des termes indiqués
                        foreach((array) $value as $value) {
                            $this->request->addFilter($dsl->term($name, $value));
                        }
                    }

                    // Filtre spécial
                    elseif ($this->isSpecialFilter($name)) {
                        // Croisés en "ou" : la requête doit matcher l'un des termes indiqués
                        $this->request->addFilter($dsl->terms($name, (array) $value));
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
        $types = $in ? array_intersect($this->types, $in) : $this->types;
        $this->request->setTypes($types);

        // Définit la représentation sous forme d'équation de la requête
        $this->request->setEquation($this->getEquation());

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
     * Indique si un champ est un filtre standard.
     *
     * Par convention, les filtres standard ont un nom de la forme "xxx.filter" (topic.filter, author.filter, etc.)
     * et sont croisés en "et".
     *
     * @param string $field
     *
     * @return bool
     */
    protected function isFilter($field)
    {
        return substr($field, -7) === '.filter';
    }

    /**
     * Indique si un champ est un filtre spécial.
     *
     * Certains filtres ne sont pas de la forme "xxx.filter" (status, createdby...) : ce sont des filtres spéciaux qui
     * sont croisés en "ou".
     *
     * Remarque : la liste est codée en dur.
     *
     * @param string $field
     *
     * @return bool
     */
    protected function isSpecialFilter($field)
    {
        static $filters = [
            'in' => true,
            'is' => true,
            'type' => true,
            'status' => true,
            'createdby' => true,
            'parent' => true,
        ];

        return isset($filters[$field]);
    }

    /**
     *
     * @param string $name
     * @param string|null $value
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
                        } elseif(count($args[$name]) === 1) {
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
        if ($page === SearchRequest::DEFAULT_PAGE) {
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
     * 'createdby+' : return ['first-author' => ['order' => 'asc'], 'creation' => ['order' => 'desc']] // auteur croissant puis date décroissante
     */
}
