<?php
/**
 * This file is part of the Fooltext package.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Fooltext
 * @subpackage  Document
 * @author      Daniel Ménard <Daniel.Menard@laposte.net>
 * @version     SVN: $Id: AnalyzerInterface.php 10 2011-12-13 15:45:47Z daniel.menard.35@gmail.com $
 */
namespace Fooltext\Document;

class Document extends \ArrayObject implements DocumentInterface
{
    public function __construct(array $data = array())
    {
        parent::__construct($data, \ArrayObject::ARRAY_AS_PROPS);
    }

    /*
     * Lorsqu'on essaie d'accèder à un champ qui n'existe pas, l'interface
     * DocumentInterface spécifie qu'il faut retourner la valeur null.
     * Ce n'est pas le comportement par défaut de ArrayAccess qui, dans ce
     * cas, génère un warning "undefined index".
     * Pour éviter cela, on surcharge offsetGets et on teste si le champ
     * existe ou non.
     * Coupe de chance : offsetGet est appellée quand on utilise la syntaxe
     * $document['champ'] mais également quand on utilise la syntaxe
     * $document->champ.
     */
    public function offsetGet($field)
    {
        if (! isset($this[$field])) return null;
        return parent::offsetGet($field);
    }

    public function toArray()
    {
        return (array)$this;
    }

    public function __toString()
    {
        $h = '';
        foreach($this as $name=>$value)
        {
            if (is_scalar($value))
            {
                $h .= "$name: $value\n";
            }
            else
            {
                $h .= "$name: " . implode('¤', $value) . "\n";
            }
        }
        return $h;
    }


    /*
     * Les fonctions qui suivent (__get, __set, __isset, __unset) doivent
     * être implémentées parce qu'elles figurent dans l'interface
     * DocumentInterface, mais en fait, comme on initialise ArrayObject
     * avec "ARRAY_AS_PROPS", elles ne seront jamais appellées.
     */
    public function __get($field) { }
    public function __isset($field) { }
    public function __unset($field) { }
    public function __set($field,$value) { }
}