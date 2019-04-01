<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Search\MappingBuilder;

use Docalist\Search\MappingBuilder;
use InvalidArgumentException;

/**
 * Un Mapping Builder pour ElasticSearch.
 *
 * Cette classe permet de créer facilement un mapping ElasticSearch.
 *
 * Par exemple, on pourrait indexer un post WordPress avec le mapping suivant :
 * <code>
 *     $mapping->field('ID')->integer();
 *     $mapping->field('status')->text()->filter();
 *     $mapping->field('title')->text();
 *     $mapping->field('content')->text();
 *     $mapping->field('taxonomy')->text()->filter()->suggest();
 *     $mapping->template('taxonomy.*')->copyFrom('taxonomy')->copyDataTo('topic');
 * </code>
 *
 * Le mapping généré peut être obtenu avec <code>$mapping->mapping()</code> qui retourne un tableau
 * contenant le mapping elasticSearch (pour l'exemple ci-dessus, le tableau généré fait plus de 50 lignes en JSON).
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ElasticsearchMappingBuilder implements MappingBuilder
{
    /**
     * L'analyseur par défaut à utiliser pour les champs de type texte.
     *
     * @var string
     */
    protected $defaultAnalyzer;

    /**
     * Le mapping en cours de génération.
     *
     * @var array
     */
    protected $mapping;

    /**
     * Une référence vers l'objet en cours.
     *
     * Initialement, c'est l'objet mapping, mais si on appelle innerObject() ou nested() l'objet en cours est
     * initialisé avec le champ en cours et les méthodes addField(), text(), date(), etc. modifieront cet objet
     * et non le mapping global.
     *
     * Une fois qu'on a terminé le paramétrage d'un innerObject ou d'un nested, il faut obligatoirement appeller
     * la méthode done() pour remonter d'un niveau.
     *
     * @var array
     */
    protected $currentObject;

    /**
     * Une référence vers le dernier champ ou le dernier template ajouté.
     *
     * @var array
     */
    protected $last;

    /**
     * Version de elasticsearch.
     *
     * @var string
     */
    protected $esVersion;

    /**
     * Code à utiliser pour un champ de type "text".
     * Avec ES >= 5, c'est "text", avant c'était "string".
     *
     * @var string
     */
    protected $textType;

    /**
     * Construit un générateur de mappings.
     *
     * @param string $esVersion Version de elasticsearch.
     */
    public function __construct($esVersion)
    {
        $this->esVersion = $esVersion;

        if (version_compare($esVersion, '4.99', '>=')) { // minimum '5.0.0-alpha' (5-alpha < 5)
            $this->textType = 'text';
        } else {
            $this->textType = 'string';
        }

        $this->reset()->setDefaultAnalyzer('text');
    }

    // -------------------------------------------------------------------------
    // Interface MappingBuilder
    // -------------------------------------------------------------------------

    public function getDefaultAnalyzer()
    {
        return $this->defaultAnalyzer;
    }

    public function setDefaultAnalyzer($defaultAnalyzer)
    {
        $this->defaultAnalyzer = $defaultAnalyzer;

        return $this;
    }

    public function addField($name)
    {
        if (isset($this->currentObject[$name])) {
            throw new InvalidArgumentException("Field '$name' is already defined");
        }

        $this->currentObject[$name] = [];

        $this->last = & $this->currentObject[$name];

        return $this;
    }
/*
    public function addProperties($properties)
    {
        $this->last += $properties;

        return $this;
    }
*/
    public function addTemplate($match)
    {
        // https://www.elastic.co/guide/en/elasticsearch/reference/current/dynamic-templates.html

        if (isset($this->mapping['dynamic_templates'][$match])) {
            throw new InvalidArgumentException("Dynamic template '$match' is already defined");
        }

        $pos = count($this->mapping['dynamic_templates']);

        $this->mapping['dynamic_templates'][$pos] = [
            $match => [
                'path_match' => $match,
                'mapping' => [],
            ],
        ];

        $this->last = & $this->mapping['dynamic_templates'][$pos][$match]['mapping'];

        return $this;
    }

    /**
     * Indique que le champ est de type objet imbriqué.
     *
     * Tous les appels suivants à la méthode addField() ajouteront des champs à l'objet jusqu'à ce que done() soit
     * appellée.
     *
     * @return self
     */
    public function innerObject()
    {
        // https://www.elastic.co/guide/en/elasticsearch/reference/master/object.html
        $this->last['type'] = 'object';
        $this->last['properties'] = [];

        $this->currentObject = & $this->last['properties'];

        return $this;
    }

    /**
     * Indique que le champ est de type nested.
     *
     * Tous les appels suivants à la méthode addField() ajouteront des champs à l'objet jusqu'à ce que done() soit
     * appellée.
     *
     * @return self
     */
    public function nested()
    {
        // https://www.elastic.co/guide/en/elasticsearch/reference/master/nested.html
        $this->last['type'] = 'nested';
        $this->last['properties'] = [];

        $this->currentObject = & $this->last['properties'];

        return $this;
    }

    /**
     * Indique que la création d'un objet créé via un appel à innerObject() ou nested() est terminée.
     *
     * @return self
     */
    public function done()
    {
        $this->currentObject = & $this->mapping['properties'];

        return $this;
    }

    public function keyword()
    {
        // https://www.elastic.co/guide/en/elasticsearch/reference/current/string.html
        // https://github.com/elastic/elasticsearch/issues/12394
        if (version_compare($this->esVersion, '5', '<')) {
            $this->last['type'] = 'string';
            $this->last['index'] = 'not_analyzed';
        } else {
            $this->last['type'] = 'keyword';
        }

        return $this;
    }

    public function hierarchy($analyzer = 'hierarchy')
    {
        // Version 2.x
        if (version_compare($this->esVersion, '5', '<')) {
            return $this
                ->text($analyzer)
                ->setProperty('search_analyzer', 'keyword');
        }

        // Versions 5.x, 6.x
        return $this
            ->text($analyzer)
            ->setProperty('fielddata', true)
            ->setProperty('search_analyzer', 'keyword');
    }

    public function literal()
    {
        // https://www.elastic.co/guide/en/elasticsearch/reference/current/string.html
        $this->last['type'] = $this->textType;
        $this->last['analyzer'] = 'text';

        return $this;
    }

    public function text($analyzer = null)
    {
        // https://www.elastic.co/guide/en/elasticsearch/reference/current/string.html
        is_null($analyzer) && $analyzer = $this->defaultAnalyzer;
        $this->last['type'] = $this->textType;
        $this->last['analyzer'] = $analyzer;

        return $this;
    }

    public function setProperty($property, $value)
    {
        $this->last[$property] = $value;

        return $this;
    }

    public function integer($type = 'long')
    {
        // https://www.elastic.co/guide/en/elasticsearch/reference/current/number.html
        if (! in_array($type, ['long', 'integer', 'short', 'byte'])) {
            throw new InvalidArgumentException("Invalid integer type '$type'");
        }
        $this->last['type'] = $type;
        $this->last['ignore_malformed'] = true;
        $this->last['coerce'] = false;

        return $this;
    }

    public function decimal($type = 'float')
    {
        // https://www.elastic.co/guide/en/elasticsearch/reference/current/number.html
        if (! in_array($type, ['double', 'float'])) {
            throw new InvalidArgumentException("Invalid decimal type '$type'");
        }
        $this->last['type'] = $type;
        $this->last['ignore_malformed'] = true;
        $this->last['coerce'] = false;

        return $this;
    }

    public function date()
    {
        // https://www.elastic.co/guide/en/elasticsearch/reference/current/date.html
        $this->last['type'] = 'date';
        $this->last['format'] = $this->getDateFormats();
        $this->last['ignore_malformed'] = true;

        return $this;
    }

    public function dateTime()
    {
        // https://www.elastic.co/guide/en/elasticsearch/reference/current/date.html
        $this->last['type'] = 'date';
        $this->last['format'] = $this->getDateTimeFormats();
        $this->last['ignore_malformed'] = true;

        return $this;
    }

    public function boolean()
    {
        // https://www.elastic.co/guide/en/elasticsearch/reference/current/boolean.html
        $this->last['type'] = 'boolean';

        return $this;
    }

    public function binary()
    {
        // https://www.elastic.co/guide/en/elasticsearch/reference/current/binary.html
        $this->last['type'] = 'binary';

        return $this;
    }

    public function ipv4()
    {
        // https://www.elastic.co/guide/en/elasticsearch/reference/current/ip.html
        $this->last['type'] = 'ip';

        return $this;
    }

    public function geoPoint()
    {
        // https://www.elastic.co/guide/en/elasticsearch/reference/current/geo-point.html
        $this->last['type'] = 'geo_point';
        $this->last['ignore_malformed'] = true;
        $this->last['coerce'] = false;

        return $this;
    }

    public function url()
    {
        // Remarque : n'existe pas dans ElasticSearch, on crée simplement un champ "string" avec l'analyseur "url".
        $this->last['type'] = $this->textType;
        $this->last['analyzer'] = 'url';

        return $this;
    }

    public function filter()
    {
        if (version_compare($this->esVersion, '5', '<')) {
            $this->last['fields']['filter'] = [
                'type' => 'string',
                'index' => 'not_analyzed',
            ];
        } else {
            $this->last['fields']['filter'] = [
                'type' => 'keyword',
            ];
        }

        return $this;
    }

    public function suggest()
    {
        // https://www.elastic.co/guide/en/elasticsearch/reference/current/search-suggesters-completion.html
        $this->last['fields']['suggest'] = [
            'type' => 'completion',
            'analyzer' => 'suggest',
            'search_analyzer' => 'suggest',
        ];

        return $this;
    }

    public function copyDataTo($field)
    {
        // https://www.elastic.co/guide/en/elasticsearch/reference/current/copy-to.html
        $this->last['copy_to'] = $field;

        return $this;
    }

    public function copyFrom($field)
    {
        if (! isset($this->currentObject[$field])) {
            throw new InvalidArgumentException("Field '$field' not found");
        }

        $this->last = $this->currentObject[$field];

        // La ligne ci-dessus est difficile à comprendre car on voit mal comment ça peut faire une copie du mapping.
        // Cela fonctionne car :
        // - $this->last est une référence vers le mapping actuel du dernier champ ou template créé.
        // - la ligne ci-dessus ne modifie pas cette référence, elle affecte le mapping de $field à l'endroit
        //   où "pointe" cette référence.
        // - On écrase donc le mapping existant du champ en cours, et on le remplace par le mapping de $field.
        // - On fait donc bien une copie et la référence elle-même n'a pas été modifiée (on n'a pas $this->last = &xxx).

        return $this;
    }

    public function getMapping()
    {
        return $this->mapping;
    }

    public function reset()
    {
        unset($this->last);

        $this->mapping = [
            // Stocke la version de docalist-search qui a créé ce type
            // https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-meta-field.html
            '_meta' => [
                'docalist-search' => docalist('docalist-search')->getVersion(),
            ],

            // Par défaut le mapping est dynamique
            // https://www.elastic.co/guide/en/elasticsearch/reference/current/dynamic.html
            'dynamic' => true,

            // La détection de dates et de nombres est désactivée
            // https://www.elastic.co/guide/en/elasticsearch/reference/current/dynamic-field-mapping.html#date-detection
            'date_detection' => false,

            // La détection des nombres est désactivée
            // https://www.elastic.co/guide/en/elasticsearch/reference/current/dynamic-field-mapping.html#numeric-detection
            'numeric_detection' => false,

            // Liste des templates de champs dynamiques
            // https://www.elastic.co/guide/en/elasticsearch/reference/current/dynamic-templates.html
            'dynamic_templates' => [],

            // Liste des champs
            // https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-types.html
            'properties' => [],
        ];

        // Le champ _all n'est pas utilisé dans docalist-search
        // Avec ES >= 6, il est deprecated (non activé, on n'a rien à faire)
        // Avec ES 2 ou 5, il est actif par défaut, on le désactive
        // https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-all-field.html
        if (version_compare($this->esVersion, '6', '<')) {
            $this->mapping['_all'] = ['enabled' => false];
            $this->mapping['include_in_all'] = false;
        }

        return $this->done();
    }

    // -------------------------------------------------------------------------
    // Privé
    // -------------------------------------------------------------------------

    /**
     * @return string
     *
     * @see http://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-date-format.html
     */
    protected function getDateFormats()
    {
        // @see https://fr.wikipedia.org/wiki/Date#Variations_par_pays
        $formats = [
            // big endian
            'yyyy-MM-dd', 'yyyy-MM',
            'yyyy/MM/dd', 'yyyy/MM',
            'yyyy.MM.dd', 'yyyy.MM',
            'yyyyMMdd'  , 'yyyyMM' ,
            'yyyy',     // important : doit être en dernier
                        // sinon "19870101" est reconnu comme une année yyyy et non comme le 01/01/1987

            // little endian
            'dd-MM-yyyy', 'MM-yyyy',
            'dd/MM/yyyy', 'MM/yyyy',
            'dd.MM.yyyy', 'MM.yyyy',
         // 'ddMMyyyy'  , 'MMyyyy' , non disponible car ça donne le même format que yyyyMMdd et yyyyMM
        ];

        return implode('||', $formats);
    }

    /**
     * @return string
     *
     * @see http://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-date-format.html
     */
    protected function getDateTimeFormats()
    {
        // Tous les formats de getDateFormats() qui sont précis au jour près
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
            '',
        ];

        $formats = [];

        foreach ($dates as $date) {
            foreach ($times as $time) {
                $formats[] = $date . $time;
            }
        }

        return implode('||', $formats);
    }
}
