<?php
/**
 * This file is part of the Fooltext package.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Fooltext
 * @subpackage  Schema
 * @author      Daniel Ménard <Daniel.Menard@laposte.net>
 * @version     SVN: $Id$
 */
namespace Fooltext\Schema;

use Fooltext\Schema\Field;

/**
 * Représente un schéma.
 *
 *
 */
class Schema extends Nodes
{
    /**
     * Propriétés par défaut du schéma.
     *
     * @var array
     */
    protected static $defaultProperties = array
    (
        // Version du format. Initialisé dans le constructeur pour qu'on la voit dans le xml
        'version' => null, // lecture seule

        // Un libellé court décrivant la base
    	'label' => '',

        // Description, notes, historique des modifs...
        'description' => '',

        // Liste par défaut des mots-vides à ignorer lors de l'indexation
        'stopwords' => '',

        // Faut-il indexer les mots vides ?
        'indexstopwords' => true,

        // Date de création du schéma
        'creation' => null,

        // Date de dernière modification du schéma
        'lastupdate' => null,

        // Nom du champ utilisé comme numéro unique des documents
        'docid' => '@field',

        // Dernier id de collection utilisé (a, b, ... z, aa, ab... zzz...)
        '_lastid' => null,
    );


    protected static $labels = array
    (
        'main' => 'Schéma',
        'add' => 'Nouvelle propriété',
        'remove' => 'Supprimer la propriété', // %1=name, %2=type
    );


    protected static $icons = array
    (
        'image' => 'gear.png',
        'add' => 'gear--plus.png',
        'remove' => 'gear--minus.png',
    );

    /**
     * Liste des collections définies dans la base.
     *
     * @var array(Collection)
     */
    protected $collections = array();

    /**
     * Crée un nouveau schéma.
     *
     * Un noeud contient automatiquement toutes les propriétés par défaut définies
     * pour ce type de noeud et celles-ci apparaissent en premier.
     *
     * @param array $properties propriétés du noeud.
     */
    public function __construct(array $properties = array())
    {
        if (isset($properties['collections']))
        {
            foreach($properties['collections'] as $collection) $this->addCollection($collection);
            unset($properties['collections']);
        }

        parent::__construct($properties);
        $this->version = 2;
    }


    /**
     * Crée un schéma depuis un source xml.
     *
     * @param string $xmlSource
     * @return Schema
     * @throws \Exception
     */
    public static function fromXml($xmlSource)
    {
        // Crée un document XML
        $xml=new \domDocument();
        $xml->preserveWhiteSpace = false;

        // gestion des erreurs : voir comment 1 à http://fr.php.net/manual/en/function.dom-domdocument-loadxml.php
        libxml_clear_errors(); // >PHP5.1
        libxml_use_internal_errors(true);// >PHP5.1

        // Charge le document
        if (! $xml->loadXML($xmlSource))
        {
            $h="Schéma incorrect, ce n'est pas un fichier xml valide :<br />\n";
            foreach (libxml_get_errors() as $error)
                $h.= "- ligne $error->line : $error->message<br />\n";
            libxml_clear_errors(); // libère la mémoire utilisée par les erreurs
            throw new \Exception($h);
        }

        // Convertit le schéma xml en objet
        return self::_fromXml($xml->documentElement);
    }


    /**
     * Sérialise le schéma au format xml.
     *
     * @param true|false|int $indent
     * - false : aucune indentation, le xml généré est compact
     * - true : le xml est généré de façon lisible, avec une indentation de 4 espaces.
     * - x (int) : xml lisible, avec une indentation de x espaces.
     *
     * @return string
     */
    public function toXml($indent = false)
    {
        $xml = new \XMLWriter();
        $xml->openMemory();
        if ($indent === true) $indent = 4; else $indent=(int) $indent;
        if ($indent > 0)
        {
            $xml->setIndent(true);
            $xml->setIndentString(str_repeat(' ', $indent));
        }
        $xml->startDocument('1.0', 'utf-8', 'yes');

        $xml->startElement(NodesTypes::classToNodetype(get_class($this)));
        $this->_toXml($xml);
        $xml->endElement();

        $xml->endDocument();
        return $xml->outputMemory(true);
    }


    /**
     * Crée un schéma à partir d'une chaine au format JSON.
     *
     * @param string $json
     * @return Schema
     */
    public static function fromJson($json)
    {
        $array = json_decode($json, true);

        if (is_null($array))
            throw new \Exception('JSON invalide');

        return self::fromArray($array);
    }

    /**
     * Sérialise le schéma au format Json.
     *
     * @param true|false|int $indent
     * - false : aucune indentation, le json généré est compact
     * - true : le json est généré de façon lisible, avec une indentation de 4 espaces.
     * - x (int) : json lisible, avec une indentation de x espaces.
     *
     * @return string
     */
    public function toJson($indent = false)
    {
        if (! $indent) return '{' . $this->_toJson() . '}';

        if ($indent === true) $indent = 4; else $indent=(int) $indent;
        $indentString = "\n" . str_repeat(' ', $indent);

        $h = "{";
        $h .= $this->_toJson($indent, $indentString, ': ');
        if ($indent) $h .= "\n";
        $h .= '}';

        return $h;
    }


    /**
     * Retourne le schéma en cours.
     *
     * Pour un Schéma, getSchema() n'a pas trop d'utilité, mais ça permet
     * d'interrompre la chaine getSchema() des classes dérivées qui font toutes
     * return parent::getSchema().
     *
     * @return $this
     */
    public function getSchema()
    {
        return $this;
    }

    protected function setStopwords($stopwords)
    {
        if (is_string($stopwords))
        {
            $stopwords = str_word_count($stopwords, 1, '0123456789@_');
        }
        elseif (is_array($stopwords))
        {
            $stopwords = array_values($stopwords);
        }
        $stopwords = array_fill_keys($stopwords, true);

        $this->properties['stopwords'] = $stopwords;
    }

    public function addCollection(Collection $collection)
    {
        if (! is_null($collection->_id)) throw new \Exception('La collection a déjà un _id');

        if (is_null($this->_lastid))
        {
            $collection->_id = $this->_lastid = 'a';
        }
        else
        {
            $collection->_id = ++$this->_lastid;
        }

        $this->addChild($this->collections, $collection);

        return $this;
    }

    /**
     * Retourne la collection dont le nom est indiquée ou null si elle n'existe pas.
     *
     * @param string $name
     * @return Collection
     */
    public function getCollection($name)
    {
        if (isset($this->collections[$name])) return $this->collections[$name];
        throw new \Exception('Collection inexistante');
    }

    public function getCollections()
    {
        return $this->collections;
    }
}