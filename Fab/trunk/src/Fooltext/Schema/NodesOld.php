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
 * Classe abstraite représentant une collection de noeuds.
 *
 * Une collection de noeud est un type particulier de {@link Node noeud} qui
 * peut contenir d'autres noeuds.
 *
 * Chaque collection définit les types des noeuds qui sont autorisés comme fils
 * (cf {@link getValidChildren()} et {@link isValidChildren()}).
 *
 * Certains noeuds sont prédéfinis et existent toujours au sein de la collection
 * (cf. {@link getDefaultChildren()}.
 *
 * La collection peut être manipulée en utilisant les méthodes {@link addChild()},
 * {@link getChildren()}, {@link getChild()}, {@link hasChildren()},
 * {@link hasChild()}, {@link removeChildren()} et {@link removeChild()}.
 *
 * @package     Fooltext
 * @subpackage  Schema
 */
abstract class NodesOLD extends Node
{
    /**
     * Fils par défaut du noeud.
     *
     * Cette propriété est destinée à être surchargée par les classes descendantes.
     *
     * @var array
     */
    protected static $defaultChildren = array();


    /**
     * Types des noeuds pouvant être ajoutés comme fils.
     *
     * Cette propriété est destinée à être surchargée par les classes descendantes.
     *
     * @var array
     */
    protected static $validChildren = array();


    /**
     * Liste des fils actuels de la collection.
     *
     * @var array
     */
    protected $children = null;


    /**
     * Crée un nouveau noeud.
     *
     * Un noeud contient automatiquement toutes les propriétés par défaut définies
     * pour ce type de noeud.
     *
     * @param array $properties propriétés du noeud.
     */
    public function __construct(array $properties = array())
    {
        $children = isset($properties['children']) ? $properties['children'] : array();
        unset($properties['children']);

        parent::__construct($properties);

        foreach($children as $child)
        {
            $this->addChild(self::fromArray($child));
        }

        foreach(static::$defaultChildren as $name)
        {
            if (! $this->hasChild($name)) $this->addChild(self::create($name));
        }
    }


    /**
     * Ajoute un noeud fils à la propriété children du noeud.
     *
     * @param Node $child le noeud fils à ajouter
     *
     * @return $this
     *
     * @throws Exception si le noeud n'a pas de nom ou si ce nom existe déjà ou ce type
     * de noeud n'est pas autorisé comme enfant.
     */
    protected function addChild(Node $child)
    {
        if (! self::isValidChild($child))
        {
            throw new \Exception
            (
        		'Un noeud de type "' . $this->getType() .
        		'" ne peut pas contenir des noeuds de type "' . $child->getType() . '"'
	        );
        }

        $name = $child->name;
        if (empty($name))
        {
            throw new \Exception('Le noeud fils doit avoir un nom');
        }

        if ($this->hasChild($name))
        {
            throw new \Exception("Il existe déjà un fils portant le nom $name");
        }

        $child->setParent($this);

        if (is_null($this->children)) $this->children = array();
        $this->children[$name] = $child;

        return $this;
    }


    /**
     * Retourne tous les noeuds fils.
     *
     * @return array()
     */
    protected function getChildren()
    {
        return isset($this->children) ? $this->children : array();
    }


    /**
     * Retourne le noeud fils dont le nom est indiqué ou null si le noeud demandé n'existe pas.
     *
     * @param string $name
     * @return null|Node
     */
    protected function getChild($name)
    {
        return isset($this->children[$name]) ? $this->children[$name] : null;
    }


    /**
     * Indique si le noeud contient des noeuds enfants.
     *
     * @return bool
     */
    public function hasChildren()
    {
        return isset($this->children);
    }


    /**
     * Indique si le noeud fils dont le nom est indiqué existe.
     *
     * @param string $name
     */
    protected function hasChild($name)
    {
        return isset($this->children[$name]);
    }


    /**
     * Supprime tous les noeuds enfants.
     *
     * Sans effet si le noeud ne contient aucun fils.
     *
     * @return $this
     */
    protected function removeChildren()
    {
        unset($this->children);
        return $this;
    }


    /**
     * Supprime le noeud fils dont le nom est indiqué.
     *
     * Sans effet si le noeud indiqué n'existe pas.
     *
     * @param string $name
     * @return $this
     */
    protected function removeChild($name)
    {
        unset($this->children[$name]);
        if (count($this->children) === 0) unset($this->children);
        return $this;
    }


    /**
     * Retourne les fils par défaut du noeud.
     *
     * @return array()
     */
    protected static function getDefaultChildren()
    {
        return static::$defaultChildren;
    }


    /**
     * Retourne les types de noeuds autorisés comme fils de ce noeud.
     *
     * @return array()
     */
    public static function getValidChildren()
    {
        return static::$validChildren;
    }


    /**
     * Indique si le noeud passé en paramètre peut être ajouté comme fils
     * au noeud en cours.
     *
     * @return bool
     */
    protected static function isValidChild(Node $child)
    {
        return in_array($child->getType(), static::$validChildren); // todo: utiliser un isset ?
    }


    /**
     * Convertit la collection de noeuds en tableau.
     *
     * @return array
     */
    public function toArray()
    {
        $array = parent::toArray();
        if (isset($this->children))
        {
            $children = array();
            foreach($this->children as $child)
            {
                $children[] = $child->toArray();
            }
            $array['children'] = $children;
        }

        return $array;
    }


    /**
     * Méthode utilitaire utilisée par {@link \Fooltext\Schema}. Ajoute les propriétés
     * et les fils du noeud dans le XMLWriter passé en paramètre.
     *
     * Le tag englobant doit avoir été généré par l'appellant.
     *
     * @param \XMLWriter $xml
     */
    protected function _toXml(\XMLWriter $xml)
    {
        parent::_toXml($xml);

        if (isset($this->children))
        {
            $xml->startElement('children');
            foreach($this->children as $child)
            {
                $xml->startElement($child->getType());
                $child->_toXml($xml);
                $xml->endElement();
            }
            $xml->endElement();
        }

        return $this;
    }

    protected function _toJson($indent = false, $currentIndent = '', $colon = ':')
    {
        $h = parent::_toJson($indent, $currentIndent, $colon);
        if (isset($this->children))
        {
            $h .= ',' . $currentIndent;
            $h .= json_encode('children') . $colon;
            $h .= $currentIndent . "[";
            $currentIndent = $currentIndent . str_repeat(' ', $indent);
            $childIndent = $currentIndent . str_repeat(' ', $indent);
            foreach($this->children as $child)
            {
                $h .= $currentIndent . '{';
                $h .= $child->_toJson($indent, $childIndent, $colon);
                $h .= $currentIndent. '},';
            }
            $h = rtrim($h, ',');
            $h .= substr($currentIndent, 0, -$indent) . "]";
        }
        return $h;
    }
}