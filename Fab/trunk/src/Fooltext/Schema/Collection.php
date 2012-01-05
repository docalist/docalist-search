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
 * Collection de documents.
 */
class Collection extends Nodes
{
    protected static $defaultProperties = array
    (
        // Nom de la collection
        'name' => '',

        // Identifiant (préfixe) de la collection (code unique : a, b, aa, aaa)
		'_id' => null,

        // Dernier id utilisé pour numéroter les champs (entier positif non nul)
    	'_lastid' => 0,

    	// Nom de la classe utilisée pour représenter les documents
        // présents dans cette collection. Doit hériter de Fooltext\Document\Document
        'documentClass' => '\\Fooltext\\Document\\Document',
    );


    /**
     * Tableau permettant d'obtenir le nom d'un champ à partir de son id
     *
     * @var array FieldName => FieldId
     */
    protected $id = array();

    /**
    * Liste des collections présentes dans un schéma.
    *
    * @var array
    */
//     protected static $defaultChildren = array('fields','aliases');

    protected static $labels = array
    (
        'main' => 'Liste des champs',
    );

    protected static $icons = array
    (
        'image' => 'zone--arrow.png',
    );


    /**
     * @var array
     */
    protected $fields = array();
    protected $aliases = array();

    public function __construct(array $properties = array())
    {
        if (isset($properties['fields']))
        {
            foreach($properties['fields'] as $field) $this->addField($field);
            unset($properties['fields']);
        }

        if (isset($properties['aliases']))
        {
            foreach($properties['aliases'] as $alias) $this->addAlias($alias);
            unset($properties['aliases']);
        }

        parent::__construct($properties);
        $this->version = 2;
    }

    public function addField($field)
    {
        // Vérifie que $field est un champ
        if(! $field instanceof Field) $field = new Field($field);

        // Attribue un id au champ
        if (! is_null($field->_id)) throw new \Exception('Le champ a déjà un _id');
        $field->_id = ++$this->_lastid;

        // Stocke le champ
        $this->addChild($this->fields, $field);
        $this->id[$field->_id] = $field->name;

        return $this;
    }

    // retourne un champ par nom ou par id
    public function getField($name)
    {
        if (isset($this->fields[$name])) return $this->fields[$name];
        if (isset($this->id[$name])) return $this->fields[$this->id[$name]];
        throw new \Exception("Champ inexistant $name");
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function deleteField($name)
    {
        if (isset($this->fields[$name]))
        {
            unset($this->id[$this->fields[$name]->_id]);
            unset($this->fields[$name]);
            return $this;
        }

        throw new \Exception("Champ inexistant $name");
    }

    public function getFieldName($id)
    {
        if (isset($this->id[$id])) return $this->id[$id];
        return null;
    }
}