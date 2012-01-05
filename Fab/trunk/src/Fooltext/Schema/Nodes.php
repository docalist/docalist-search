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
abstract class Nodes extends Node
{
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

        if (method_exists($this, $getter))
        {
            return $this->$getter($name);
        }

        if (property_exists($this, $name))
        {
            return $this->$name;
        }

        if (array_key_exists($name, $this->properties))
        {
            return $this->properties[$name];
        }

        return null;
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
        if (method_exists($this, $setter))
        {
            return $this->$setter($value);
        }

        if (property_exists($this, $name))
        {
            throw new Exception\ReadonlyProperty($name);
        }
        //parent::__set($name, $value);
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
        if (property_exists($this, $name))
        {
            return true;
        }

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
        $setter = 'set' . ucfirst($name);
        if (method_exists($this, $setter))
        {
            return $this->$setter(null);
        }

        if (property_exists($this, $name))
        {
            throw new Exception\ReadonlyProperty($name);
        }

        parent::__unset($name);
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
    protected function addChild(array & $where, Node $child)
    {
        $name = $child->name;
        if (empty($name))
        {
            throw new \Exception('Le noeud à ajouter doit avoir un nom');
        }

        if (isset($where[$name]))
        {
            throw new \Exception("Il existe déjà un noeud avec le nom $name");
        }

        $child->parent = $this;

        $where[$name] = $child;

        return $this;
    }


    /**
     * Convertit la collection de noeuds en tableau.
     *
     * @return array
     */
//     public function toArray()
//     {
//         $array = parent::toArray();
//         if (isset($this->children))
//         {
//             $children = array();
//             foreach($this->children as $child)
//             {
//                 $children[] = $child->toArray();
//             }
//             $array['children'] = $children;
//         }

//         return $array;
//     }


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