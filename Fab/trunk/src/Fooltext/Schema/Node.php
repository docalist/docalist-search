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

/**
 * Classe de base (abstraite) représentant un noeud dans un schéma.
 *
 * Un noeud est un objet qui peut contenir des propriétés (cf. {@link __get()},
 * {@link __set()}, {@link __isset()}, {@link __unset()} et {@link getProperties()}).
 *
 * Un noeud dispose de propriétés par défaut (cf. {@link getKnownProperties()})
 * qui sont créées automatiquement et sont toujours disponibles.
 *
 * Un noeud peut être créé via {@link __construct() son constructeur} ou en
 * utilisant les méthodes statiques disponibles (cf. {@link create()} et
 * {@link fromArray()}).
 *
 * Un noeud est toujours d'un type donné (cf. {@link getType()}. Lorsqu'un
 * noeud est sérialisé sous forme de tableau (format json, tableau php),
 * le type du noeud figure dans une propriété supplémentaire "_nodetype".
 *
 * Un noeud peut être ajouté dans une {@link NodesCollection collection de noeuds}.
 * Pour cela il faut qu'il ait une propriété "name" indiquant son nom.
 *
 * Une fois qu'un noeud a été ajouté à une collection, il dispose d'un parent
 * (cf {@link getParent()}) qui permet d'accèder au schéma ({@link getSchema()}.
 *
 * @package     Fooltext
 * @subpackage  Schema
 */
abstract class Node
{
    /**
     * Propriétés prédéfinies et valeurs par défaut des propriétés de ce type de noeud.
     *
     * Cette propriété est destinée à être surchargée par les classes descendantes.
     *
     * $knownProperties indique à la fois :
     * - la liste des propriétés prédéfinies
     * - le type de chacune des propriétés
     * - la valeur par défaut de chacune des propriétés
     * - éventuellement, la liste des valeurs autorisées.
     * - par inférence, le type de contrôle utilisé dans l'éditeur de schéma pour
     *   modifier cette propriété.
     *
     * Pour cela, un codage est utilisé. La valeur associé à chaque propriété par
     * défaut peut être :
     *
     * - STRING : type par défaut, une textarea fullwidth autoheight sera utilisée
     *   dans l'éditeur de schéma pour éditer la propriété. La chaine contient la
     *   valeur par défaut de la propriété.
     * - INT : une zone de texte de type "number" (html5) d'une longueur maximale
     *   de 5 caractères maximum sera utilisée. L'entier contient la valeur par
     *   défaut de la propriété.
     * - BOOLEAN : la propriété sera représentée par une case à cocher. Le booléen
     *   indique la valeur par défaut de la propriété (true : la case à cocher sera
     *   cochée par défaut, false, elle sera décochée par défaut).
     * - ARRAY : la propriété sera représentée par un select dans lequel l'utilisateur
     *   peut sélectionner l'une des valeurs qui figurent dans le tableau. Le premier
     *   élément du tableau représente la valeur par défaut de la propriété.
     * - NULL : propriété en lecture seule. Une textarea "disabled" sera utilisée pour afficher
     *   la propriété. L'utilisateur peut voir la valeur de la propriété mais ne peut pas la
     *   modifier. Il n'est pas possible d'indiquer dans ce cas une valeur par défaut.
     *
     * @var array
     */
    protected static $knownProperties = array();


    /**
     * Définit les libellés à utiliser pour ce type de noeud.
     *
     * $labels est un tableau avec les clés suivantes :
     * - 'main' : libellé utilisé pour un noeud de ce type (par exemple pour
     *   un noeud de type field, le libellé indiqué serait "Champ".
     * - 'add' : libellé utilisé pour ajouter un noeud de ce type
     *   (exemple : "Ajouter un champ").
     * - 'remove' : libellé utilisé pour supprimer un noeud de ce type
     *   (exemple : "Supprimer ce champ").
     *
     * @var array
     */
    protected static $labels = array
    (
        'main' => 'Noeud',
        'add' => 'Nouveau noeud de type %1', // %1 : type
        'remove' => 'Supprimer le noeud %2', // %1 : type, %2 : name
    );


    /**
     * Définit les icones à utiliser pour ce type de noeud.
     *
     * $icons est un tableau avec les clés suivantes :
     * - 'image' : icone utilisée pour représenter un noeud de ce type.
     * - 'add' : icone utilisée pour signifier "ajouter un noeud de ce type".
     * - 'remove' : icone utilisée pour indiquer "supprimer un noeud de ce type".
     *
     * Toutes les icones sont relatives au répertoire /web/modules/AdminSchemas/images.
     *
     * @var array
     */
    protected static $icons = array
    (
        'image' => 'zone.png',
        'add' => 'zone--plus.png',
        'remove' => 'zone--minus.png',
    );


    /**
     * Propriétés du noeud.
     *
     * @var array
     */
    protected $properties = array();


    /**
     * Noeud parent de ce noeud.
     *
     * Cette propriété est initialisée automatiquement lorsqu'un noeud
     * est ajouté dans une {@link NodesCollection collection}.
     *
     * @var Nodes
     */
    protected $parent = null;


    /**
     * Crée un nouveau noeud.
     *
     * Un noeud contient automatiquement toutes les propriétés par défaut définies
     * pour ce type de noeud et celles-ci apparaissent en premier.
     *
     * @param array $properties propriétés du noeud.
     */
    public function __construct(array $properties = array())
    {
        // on commence par les propriétés par défaut pour qu'elles apparaissent
        // en premier et dans l'ordre indiqué dans la classe.
        $this->properties = array_merge(static::getDefaultValue(), $properties);
    }


    /**
     * Retourne la propriété dont le nom est indiqué ou null si la propriété
     * demandée n'existe pas.
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        $getter = 'get' . ucfirst($name);
        if (method_exists($this, $getter)) return $this->$getter($name);

        return array_key_exists($name, $this->properties) ? $this->properties[$name] : null;
    }


    /**
     * Ajoute ou modifie une propriété.
     *
     * Si la valeur indiquée est <code>null</code>, la propriété est supprimée de
     * l'objet ou revient à sa valeur par défaut si c'est une propriété prédéfinie.
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value = null)
    {
        $setter = 'set' . ucfirst($name);
        if (method_exists($this, $setter)) return $this->$setter($value);

        if (is_null($value))
        {
            $this->__unset($name);
        }
        else
        {
            $this->properties[$name] = $value;
        }
    }


    /**
     * Indique si une propriété existe.
     *
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return array_key_exists($name, $this->properties);
    }


    /**
     * Supprime la propriété indiquée ou la réinitialise à sa valeur par défaut
     * s'il s'agit d'une propriété prédéfinie.
     *
     * Sans effet si la propriété n'existe pas.
     *
     * @param string $name
     *
     * @return $this
     */
    public function __unset($name)
    {
        if (isset(static::$knownProperties[$name]))
        {
            $this->properties[$name] = self::getDefaultValue($name);
        }
        else
        {
            unset($this->properties[$name]);
        }
    }


    /**
     * Retourne le type du noeud.
     *
     * La méthode retourne le {@link NodeTypes nom symbolique} associé au noeud.
     *
     * @return string
     */
//     public function getType()
//     {
//         return NodesTypes::classToNodetype(get_class($this));
//     }


    /**
     * Retourne le noeud parent de ce noeud ou <code>null</code> si le noeud
     * n'a pas encore été ajouté comme fils d'un noeud existant.
     *
     * @return \Fooltext\Schema\Node
     */
    protected function getParent()
    {
        return $this->parent;
    }


    /**
     * Modifie le parent de ce noeud.
     *
     * @param Nodes $parent
     * @return $this
     */
    protected function setParent(Nodes $parent)
    {
        $this->parent = $parent;
    }


    /**
     * Retourne le schéma dont fait partie ce noeud ou <code>null</code> si
     * le noeud n'a pas encore été ajouté à un schéma.
     *
     * @return \Fooltext\Schema
     */
    public function getSchema()
    {
        return is_null($this->parent) ? null : $this->parent->getSchema();
    }


    /**
     * Retourne un tableau contenant toutes les propriétés du noeud.
     *
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }


    /**
     * Contrairement à un objet {@link NodesCollection}, un noeud de base ne contient pas de fils.
     *
     * @return array
     */
//     public function hasChildren()
//     {
//         return false;
//     }


    /**
     * Construit un noeud à partir d'un tableau contenant ses propriétés.
     *
     * @param string $nodetype le type du noeud à créer.
     * @param array $properties un tableau contenant les propriétés du noeud créé.
     *
     * @return Node
     */
//     protected static function create($nodetype, array $properties = array())
//     {
//         $class = NodesTypes::nodetypeToClass($nodetype);
//         if (! isset($properties['name'])) $properties['name'] = $nodetype; // à revoir
//         return new $class($properties);
//     }


    /**
     * Retourne les propriétés par défaut du noeud.
     *
     * @return array()
     */
    protected static function getKnownProperties()
    {
        return static::$knownProperties;
    }


    /**
     * Retourne la valeur par défaut d'une propriété ou de l'ensemble
     * des propriétés prédéfinies si aucun nom n'est indiqué.
     *
     *
     * @param string $name
     * @return null|mixed|array Si $name a été indiqué, la méthode retourne la
     * valeur par défaut de la propriété, ou null si la propriété demandée n'est
     * pas prédéfinie.
     * Si $name est absent, la méthode retourne un tableau contenant les valeurs
     * par défaut de toutes les propriétés prédéfinies.
     */
    protected static function getDefaultValue($name = null)
    {
        // Retourne la valeur par défaut d'une propriété particulière
        if ($name)
        {
            if (isset(static::$knownProperties[$name]['default']))
            {
                return static::$knownProperties[$name]['default'];
            }

            return null;
        }

        // Retourne une tableau contenant les valeurs par défaut de toutes les propriétés
        $result = array();
        foreach(static::$knownProperties as $name => $params)
        {
            $result[$name] = isset($params['default']) ? $params['default'] : null;
        }

        return $result;
    }


    /**
     * Retourne un libellé ou tous les libellés définis pour un type
     * de noeud donné.
     *
     * @param null|string $type quand type est null, un tableau contenant
     * tous les libellés définis pour ce type de noeud est retourné.
     * Quand $type est une chaine, le libellé correspondant est retourné.
     *
     * @return string
     */
    public static function getLabels($type = null)
    {
        if (is_null($type))
        {
            return array_merge(self::$labels, static::$labels);
        }

        if (isset(static::$labels[$type]))
        {
            return static::$labels[$type];
        }

        if (isset(self::$labels[$type]))
        {
            return self::$labels[$type];
        }

        return $type;
    }


    /**
     * Retourne une icone ou toutes les icones définies pour un type
     * de noeud donné.
     *
     * @param null|string $type quand type est null, un tableau contenant
     * toutes les icones définies pour ce type de noeud est retourné.
     * Quand $type est une chaine, l'icone correspondante est retourné.
     *
     * @return string
     */
    public static function getIcons($type = null)
    {
        if (is_null($type))
        {
            return array_merge(self::$icons, static::$icons);
        }

        if (isset(static::$icons[$type]))
        {
            return static::$icons[$type];
        }

        if (isset(self::$icons[$type]))
        {
            return self::$icons[$type];
        }

        return null;
    }


    /**
     * Construit un noeud à partir d'un tableau contenant ses propriétés.
     *
     * @param array $properties un tableau contenant les propriétés du noeud.
     *
     * Le tableau doit contenir une propriété '_nodetype' qui indique le
     * type du noeud à créer.
     *
     * @throws Exception si la propriété _nodetype ne figure pas dans le
     * tableau.
     *
     * @return Node
     */
//     protected static function fromArray(array $properties)
//     {
//         if (! isset($properties['_nodetype']))
//             throw new Exception('Le tableau ne contient pas de clé "_nodetype".');

//         $nodetype = $properties['_nodetype'];
//         unset($properties['_nodetype']);

//         return self::create($nodetype, $properties);
//     }


    /**
     * Convertit le noeud en tableau.
     *
     * @return array
     */
//     public function toArray()
//     {
//         return array_merge(array('_nodetype'=>$this->getType()), $this->properties);
//     }


    /**
     * Méthode utilitaire utilisée par {@link \Fooltext\Schema::fromXml()} pour
     * charger un chéma Xml.
     *
     * @param \DOMNode $node
     * @param string $path
     * @param string $nodetype
     * @throws \Exception
     */
    protected static function _fromXml(\DOMNode $node, $path='', $nodetype=null)
    {
        /**
         * Tableau utilisé pour convertir les schémas xml de la version 1 à la version 2.
         *
         * Ce tableau contient toutes les "collections" qui existaient dans l'ancien format.
         * Pour chaque collection (on indique le path depuis la racine), on peut indiquer :
         * - true : cette collection existe toujours dans le nouveau format.
         *   Il faut donc la créer et ajouter dedans les noeuds fils.
         * - une chaine contenant un type de noeud symbolique : cela signifie que cette
         *   collection n'existe plus dans le nouveau format. Les noeuds fils doivent être
         *   ajoutés directement dans la clé children du noeud parent et doivent être créé
         *   en utilisant le type indiqué.
         */
        static $oldnodes = array
        (
        	'/schema/fields' => true,
        	'/schema/indices' => true,
        	'/schema/indices/index/fields' => 'indexfield',
        	'/schema/lookuptables' => true,
        	'/schema/lookuptables/lookuptable/fields' => 'lookuptablefield',
        	'/schema/aliases' => true,
        	'/schema/aliases/alias/indices' => 'aliasindex',
        	'/schema/sortkeys' => true,
        	'/schema/sortkeys/sortkey/fields' => 'sortkeyfield',
        );

        // Stocke le type de noeud
        $result = self::create(is_null($nodetype) ? $node->tagName : $nodetype);
        $path .= "/$node->tagName";

        // Les attributs du tag sont des propriétés de l'objet
        if ($node->hasAttributes())
            foreach ($node->attributes as $attribute)
                $result->set($attribute->nodeName, self::_xmlToValue($attribute->nodeValue));

        // Les noeuds fils du tag sont soit des propriétés, soit des objets enfants
        foreach ($node->childNodes as $child)
        {
            $childpath = "$path/$child->tagName";

            switch ($child->nodeType)
            {
                case XML_ELEMENT_NODE:
                    // Le nom de l'élément va devenir le nom de la propriété
                    $name = $child->tagName;

                    // Collection (children ou, pour les anciens formats, fields, indices, etc.)
                    if ($name === 'children')
                    {
                        foreach($child->childNodes as $child)
                        $result->addChild(self::_fromXml($child, $path));
                    }

                    elseif (isset($oldnodes[$childpath]))
                    {
                        if ($oldnodes[$childpath] === true)
                        {
                            $collection = Node::create($name);
                            foreach($child->childNodes as $child)
                            $collection->addChild(self::_fromXml($child, $childpath));

                            $result->addChild($collection);
                        }
                        else
                        {
                            foreach($child->childNodes as $child)
                            $result->addChild(self::_fromXml($child, $childpath, $oldnodes[$childpath]));
                        }
                    }

                    // Propriété
                    else
                    {
                        // Vérifie qu'on n'a pas à la fois un attribut et un élément de même nom (<database label="xxx"><label>yyy...)
                        if ($node->attributes->getNamedItem($name))
                        throw new \Exception("Erreur dans le source xml : la propriété '$name' apparaît à la fois comme attribut et comme élément");

                        // Stocke la propriété
                        $result->set($name, self::_xmlToValue($child->nodeValue)); // si plusieurs fois le même tag, c'est le dernier qui gagne
                    }
                    break;

                    // Types de noeud autorisés mais ignorés
                case XML_COMMENT_NODE:
                    break;

                    // Types de noeud interdits
                default:
                    throw new \Exception("les noeuds de type '".$child->nodeName . "' ne sont pas autorisés");
            }
        }

        return $result;
    }


    /**
     * Méthode utilitaire utilisée par {@link \Fooltext\Schema}. Ajoute les propriétés du
     * noeud dans le XMLWriter passé en paramètre.
     *
     * La méthode ne génère que les propriétés du noeud. Le tag englobant doit
     * avoir été généré par l'appellant.
     *
     * @param \XMLWriter $xml
     */
    protected function _toXml(\XMLWriter $xml)
    {
        foreach($this->properties as $name=>$value)
        {
            if (isset(static::$knownProperties[$name]) && self::getDefaultValue($name) === $value) continue;

            if (is_bool($value)) $value = $value ? 'true' : 'false';
            if (is_scalar($value))
            {
                $xml->writeElement($name, $value);
            }
            else if (is_array($value))
            {
                $xml->startElement($name);
                foreach($value as $item)
                {
                    $xml->writeElement('item', $item);
                }
                $xml->endElement();
            }
        }
    }


    /**
    * Fonction utilitaire utilisée par {@link xmlToObject()} pour convertir la
    * valeur d'un attribut ou le contenu d'un tag.
    *
    * Pour les booléens, la fonction reconnait les valeurs 'true' ou 'false'.
    * Pour les autres types scalaires, la fonction encode les caractères '<',
    * '>', '&' et '"' par l'entité xml correspondante.
    *
    * @param scalar $value
    * @return string
    */
    protected static function _xmlToValue($value)
    {
        if ($value === 'true') return true;
        if ($value === 'false') return false;
        if (is_int($value) || ctype_digit($value)) return (int) $value;
        if (is_numeric($value)) return (float)$value;
        return $value;
    }



    /**
     * Méthode utilitaire utilisée par {@link \Fooltext\Schema}. Sérialise le noeud
     * au format JSON.
     *
     * La méthode ne générère que les propriétés du noeud. La méthode appelante doit générer
     * les accolades ouvrantes et fermantes.
     *
     * @param \XMLWriter $xml
     */
    protected function _toJson($indent = false, $currentIndent = '', $colon = ':')
    {
        //$h = $currentIndent . json_encode('_nodetype') . $colon . json_encode($this->getType()) . ',';
        $h ='';
        foreach($this->properties as $name=>$value)
        {
            if (isset(static::$knownProperties[$name]) && self::getDefaultValue($name) === $value) continue;

            $h .= $currentIndent . json_encode($name) . $colon . json_encode($value) . ',';
        }

        return rtrim($h, ',');
    }
}