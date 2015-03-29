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

use InvalidArgumentException;

/**
 * Un helper pour aider à créer les mappings d'un type de document.
 *
 * Cette classe permet de créer facilement un mapping ElasticSearch.
 *
 * Par exemple, on pourrait indexer un post WordPress avec le mapping suivant :
 * <code>
 *     $mapping->field('ID')->long();
 *     $mapping->field('status')->text()->filter();
 *     $mapping->field('title')->text();
 *     $mapping->field('content')->text();
 *     $mapping->field('taxonomy')->text()->filter()->suggest();
 *     $mapping->template('taxonomy.*')->idem('taxonomy')->copyTo('topic');
 * </code>
 *
 * Le mapping généré peut être obtenu avec <code>$mapping->mapping()</code> qui
 * retourne un tableau contenant le mapping elasticSearch (pour l'exemple
 * ci-dessus, le tableau généré fait plus de 50 lignes en JSON).
 */
class MappingBuilder {
    /**
     * L'analyseur par défaut à utiliser pour les champs de type texte.
     *
     * @var string
     */
    protected $defaultAnalyzer;

    /**
     * Le mapping en cours de génération.
     *
     * @var string
     */
    protected $mapping;

    /**
     * Une référence vers le dernier champ ou le dernier template ajouté au
     * mapping.
     *
     * @var array
     */
    protected $last;

    /**
     * Construit un générateur de mappings.
     *
     * @param string $defaultAnalyzer Le nom de l'analyseur par défaut à
     * utiliser pour les champs de type texte ('text', 'fr-text', 'en-text'...)
     *
     * L'analyseur indiqué doit exister dans les settings de l'index sinon une
     * exception sera générée lors du premier appel à la méthode text().
     */
    public function __construct($defaultAnalyzer = 'text') {
        // Stocke l'analyseur par défaut
        $this->defaultAnalyzer = $defaultAnalyzer;

        // Initialise les options générales du mapping
        $this->mapping = [
            // Stocke la version de docalist-search qui a créé ce type
            // @see http://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-meta.html
            '_meta' => [
                'docalist-search' =>  docalist('docalist-search')->version(),
            ],

            // Par défaut le mapping est dynamique
            // @see http://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-object-type.html#_dynamic
            'dynamic' => true,

            // Le champ _all n'est pas utilisé
            // http://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-all-field.html#mapping-all-field
            '_all' => ['enabled' => false],
            'include_in_all' => false,

            // La détection de dates et de nombres est désactivée
            // @see http://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-root-object-type.html#_date_detection
            'date_detection' => false,
            'numeric_detection' => false,

            // Liste des templates de champs
            // @see http://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-root-object-type.html#_dynamic_templates
            'dynamic_templates' => [],

            // Liste des champs
            // @see http://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-object-type.html
            'properties' => []
        ];
    }

    /**
     * Retourne le nom de l'analyseur par défaut à utiliser pour les champs de
     * type texte.
     *
     * @return string Le nom de l'analyseur passé au constructeur ('text',
     * 'fr-text', 'en-text'...)
     */
    public function defaultAnalyzer() {
        return $this->defaultAnalyzer;
    }

    /**
     * Retourne le mapping en cours.
     *
     * @param string $field Par défaut (sans paramètres), la méthode retourne
     * la totalité du mapping en cours.
     *
     * Il est possible d'obtenir le mapping d'un champ ou d'un template en
     * indiquant son nom en paramètre.
     *
     * Exemple :
     * <code>
     *     $mapping->mapping('title');
     *     $mapping->mapping('taxonomy.*');
     * </code>
     *
     * @return array Un tableau contenant le mapping ElasticSearch généré.
     *
     * @throws InvalidArgumentException Si le nom indiqué en paramètre n'est
     * ni un nom de champ existant, ni un nom de template existant.
     */
    public function mapping($field = null) {
        // Retourne la totalité du mapping
        if (is_null($field)) {
            return $this->mapping;
        }

        // Retourne le mapping du champ indiqué
        if (isset($this->mapping['properties'][$field])) {
            return $this->mapping['properties'][$field];
        }

        // Retourne le mapping du template indiqué
        if (isset($this->mapping['dynamic_templates'][$field])) {
            return $this->mapping['dynamic_templates'][$field];
        }

        // Erreur
        throw new InvalidArgumentException("'$field' is not a field or a template");
    }

    // -------------------------------------------------------------------------
    // Méthodes qui modifient $this->last
    // -------------------------------------------------------------------------

    /**
     * Ajoute un champ dans le mapping.
     *
     * @param string $name Le nom du champ à ajouter.
     *
     * @throws InvalidArgumentException Si le champ existe déjà.
     *
     * @return self
     */
    public function field($name) {
        if (isset($this->mapping['properties'][$name])) {
            throw new InvalidArgumentException("Field '$name' already defined");
        }

        $this->mapping['properties'][$name] = [];

        $this->last = & $this->mapping['properties'][$name];

        return $this;
    }

    /**
     * Ajoute un template dans le mapping.
     *
     * @param string $match Le masque indiquant le nom des champs auxquels le
     * nouveau template ser appliqué.
     *
     * @see http://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-root-object-type.html#_dynamic_templates
     *
     * @throws InvalidArgumentException Si le champ existe déjà.
     *
     * @return self
     */
    public function template($match) {
        if (isset($this->mapping['dynamic_templates'][$match])) {
            throw new InvalidArgumentException("Dynamic template '$match' already defined");
        }

        $i = count($this->mapping['dynamic_templates']);

        $this->mapping['dynamic_templates'][$i] = [
            $match => [
                'path_match' => $match,
                'mapping' => []
            ]
        ];

        $this->last = & $this->mapping['dynamic_templates'][$i][$match]['mapping'];

        return $this;
    }

    // -------------------------------------------------------------------------
    // Types de champs
    // -------------------------------------------------------------------------

    /**
     * Crée un champ de type string (contient des caractères, mais ce n'est pas
     * du texte dans une langue donnée).
     *
     * Aucun stemming n'est appliqué au champ, c'est l'analyseur 'text' qui est
     * utilisé.
     *
     * @return self
     *
     * @see http://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-core-types.html#string
     */
    public function string() {
        $this->last['type'] = 'string';
        $this->last['analyzer'] = 'text';

        return $this;
    }

    /**
     * Crée un champ de type texte (contenant des phrases dans une langue
     * donnée).
     *
     * Par défaut (sans paramètres), c'est l'analyseur par défaut indiqué lors
     * de la création de l'objet MappingBuilder qui est utilisé pour le champ
     * mais vous pouvez passer en paramètre un analyseur de texte spécifique.
     *
     * @param string $analyzer Optionnele, l'analyseur à utiliser.
     *
     * @return self
     *
     * @throws InvalidArgumentException La méthode vérifie que l'analyseur
     * existe dans les settings de l'index et que son nom contient le mot
     * "text" (pour être sûr qu'il s'agit d'un analyseur de type texte et non
     * d'un analyseur comme "filter", "suggest" ou "url"). Une exception est
     * générée si l'un de ces deux tests échoue.
     *
     * @see http://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-core-types.html#string
     */
    public function text($analyzer = null) {
        static $analyzers = null;

        // Utilise l'analyseur par défaut si aucun n'a été indiqué
        is_null($analyzer) && $analyzer = $this->defaultAnalyzer;

        // Charge la liste des analyseurs disponibles dans les settings de l'index
        if (is_null($analyzers)) {
            $settings = apply_filters('docalist_search_get_index_settings', []);
            $analyzers = $settings['settings']['analysis']['analyzer'];
            $analyzers = array_flip(array_keys($analyzers));
        }

        // Vérifie que l'analyseur indiqué existe

        if (! isset($analyzers[$analyzer])) {
            throw new InvalidArgumentException("Analyzer '$analyzer' not found");
        }

        // Construit le mapping
        $this->last['type'] = 'string';
        $this->last['analyzer'] = $analyzer;

        return $this;
    }


    /**
     * Crée un champ nombre de type "entier long".
     *
     * @return self
     *
     * @see http://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-core-types.html#number
     */
    public function long() {
        // Construit le mapping
        $this->last['type'] = 'long';
        $this->last['ignore_malformed'] = true;

        return $this;
    }

    /**
     * Crée un champ de type date.
     *
     * @return self
     *
     * @see http://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-core-types.html#date
     */
    public function date() {
        // Construit le mapping
        $this->last['type'] = 'date';
        $this->last['format'] = $this->dateFormats();
        $this->last['ignore_malformed'] = true;

        return $this;
    }

    /**
     * Crée un champ de type "date/heure".
     *
     * @return self
     *
     * @see http://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-core-types.html#date
     */
    public function dateTime() {
        // Construit le mapping
        $this->last['type'] = 'date';
        $this->last['format'] = $this->dateTimeFormats();
        $this->last['ignore_malformed'] = true;

        return $this;
    }

    /**
     * Crée un champ de type "booléen".
     *
     * @return self
     *
     * @see http://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-core-types.html#boolean
     */
    public function bool() {
        // Construit le mapping
        $this->last['type'] = 'boolean';

        return $this;
    }

    /**
     * Crée un champ de type "binary".
     *
     * @return self
     *
     * @see http://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-core-types.html#binary
     */
    public function binary() {
        // Construit le mapping
        $this->last['type'] = 'binary';

        return $this;
    }

    /**
     * Crée un champ de type "adresse IP v4".
     *
     * @return self
     *
     * @see http://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-ip-type.html
     */
    public function ip() {
        // Construit le mapping
        $this->last['type'] = 'ip';

        return $this;
    }

    /**
     * Crée un champ de type "url".
     *
     * Remarque : ce type de champ n'existe pas dans ElasticSearch. La méthode
     * crée simplement un champ de type "string" et lui applique l'analyseur
     * "url".
     *
     * @return self
     */
    public function url() {
        // Construit le mapping
        $this->last['type'] = 'string';
        $this->last['analyzer'] = 'url';

        return $this;
    }

    // -------------------------------------------------------------------------
    // Multi-fields : filter et suggest
    // -------------------------------------------------------------------------

    public function filter() {
        $this->last['fields']['filter'] = [
            'type' => 'string',
            'index' => 'not_analyzed',
        ];

        return $this;
    }

    public function suggest() {
        $this->last['fields']['suggest'] = [
            'type' => 'completion',
            'index_analyzer' => 'suggest',
            'search_analyzer' => 'suggest',
        ];

        return $this;
    }

    // -------------------------------------------------------------------------
    // Propriétés spécifiques du mapping d'un champ
    // -------------------------------------------------------------------------

    /**
     * Ajoute ou modifie la propriété "copy_to" du champ en cours.
     *
     * @param string $field
     *
     * @return self
     *
     * @see http://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-core-types.html#copy-to
     */
    public function copyTo($field) {
        $this->last['copy_to'] = $field;

        return $this;
    }

    /**
     * Ajoute ou modifie la propriété "index_name" du champ en cours.
     *
     * @param string $indexName
     *
     * @return \Docalist\Search\MappingBuilder
     *
     * @see http://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-core-types.html#string
     */
    public function alias($indexName) {
        $this->last['index_name'] = $indexName;

        return $this;
    }

    // -------------------------------------------------------------------------
    // Recopie de mapping existant
    // -------------------------------------------------------------------------

    /**
     * Recopie le mapping d'un champ existant.
     *
     * @param string $field Le nom du champ à recopier.
     *
     * @return self
     *
     * @throws InvalidArgumentException Si le champ indiqué n'existe pas.
     */
    public function idem($field) {
        if (! isset($this->mapping['properties'][$field])) {
            throw new InvalidArgumentException("Field '$field' not found");
        }

        $this->last = $this->mapping['properties'][$field];

        // La ligne ci-dessus est difficile à comprendre car on voit mal comment
        // ça peut faire une copie du mapping. Cela fonctionne car :
        // - $this->last est une référence vers le mapping actuel du dernier
        //   champ ou template créé.
        // - la ligne ci-dessus ne modifie pas cette référence, elle affecte le
        //   mapping de $field à l'endroit ou "pointe" cette référence.
        // - On écrase donc le mapping existant du champ en cours, et on le
        //   remplace complètement par le mapping de $field.
        // - On fait donc bien une copie et la référence elle-même n'a pas été
        //   modifiée (on n'a pas écrit $this->last = & xxx).

        return $this;
    }

    // -------------------------------------------------------------------------
    // Gestion du format des dates et des heures
    // -------------------------------------------------------------------------

    /**
     *
     * @return string
     *
     * @see http://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-date-format.html
     */
    protected function dateFormats() {
        // @see https://fr.wikipedia.org/wiki/Date#Variations_par_pays
        $formats = [
            // big endian
            'yyyy-MM-dd', 'yyyy-MM', 'yyyy',
            'yyyy/MM/dd', 'yyyy/MM',
            'yyyy.MM.dd', 'yyyy.MM',
            'yyyyMMdd'  , 'yyyyMM' ,

            // little endian
            'dd-MM-yyyy', 'MM-yyyy',
            'dd/MM/yyyy', 'MM/yyyy',
            'dd.MM.yyyy', 'MM.yyyy',
         // 'ddMMyyyy'  , 'MMyyyy' , non disponible car ça donne le même format que yyyyMMdd et yyyyMM
        ];

        return implode('||', $formats);
    }

    /**
     *
     * @return string
     *
     * @see http://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-date-format.html
     */
    protected function dateTimeFormats() {
        // tous les formats de dateFormats() qui sont précis au jour près
        $dates = [
            // big endian
            'yyyy-MM-dd',

            'yyyy/MM/dd',
            'yyyy.MM.dd',
            'yyyyMMdd'  ,

            // little endian
            'dd-MM-yyyy',
            'dd/MM/yyyy',
            'dd.MM.yyyy',
         // 'ddMMyyyy'  , non disponible car ça donne le même format que yyyyMMdd
        ];

        $times = [
            ' HH:mm:ss',
            ' HH:mm',
            " HH'h'mm",
//          " HH'H'mm", // inutile, pour les littéraux, joda est insensible à la casse (source : http://joda-time.sourceforge.net/apidocs/org/joda/time/format/DateTimeFormatterBuilder.html#appendLiteral(char)).
//          " HH'h'",
//          " HH'H'",
            ''
        ];

        $formats = [];

        foreach($dates as $date) {
            foreach($times as $time) {
                $formats[] = $date . $time;
            }
        }

        return implode('||', $formats);
    }
}