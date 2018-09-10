<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2012-2016 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search;

use Docalist\Search\Mapping\Field;
use Docalist\Search\Mapping\Field\Object;
use InvalidArgumentException;

/**
 */
class Mapping extends Object
{
    /**
     * Initialise le mapping.
     *
     * @param string    $name       Nom du mapping.
     */
    public function __construct($name, $defaultTextAnalyzer = 'text')
    {
        parent::__construct($name);
        $this->setDefaultTextAnalyzer($defaultTextAnalyzer);
    }

    public function getDefaultParameters()
    {
        return [
            // Stocke la version de docalist-search qui a créé ce type
            // https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-meta-field.html
            '_meta' => [
                'docalist-search' => docalist('docalist-search')->getVersion(),
            ],

            // Par défaut le mapping est dynamique
            // https://www.elastic.co/guide/en/elasticsearch/reference/current/dynamic.html
            'dynamic' => true,

            // Le champ _all n'est pas utilisé
            // https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-all-field.html
            '_all' => ['enabled' => false],
            'include_in_all' => false,

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
    }

    /**
     * Ajoute un champ ou un template.
     *
     * Si le champ est de la forme "champ*" ou "*champ", il est ajouté comme template, sinon il est ajouté comme champ.
     *
     * @param Field $field Champ à ajouter.
     *
     * @return Field Le champ ajouté
     *
     * @throws InvalidArgumentException S'il existe déjà un champ ou un template avec ce nom .
     */
    public function add(Field $field)
    {
        // Récupère le nom du champ
        $name = $field->getName();

        // Si ce n'est pas un champ dynamique, demande à la classe parent de l'ajouter comme champ
        if (false === strpos($name, '*')) {
            return parent::add($field);
        }

        // S'il existe déjà un template avec ce nom, erreur
        if (isset($this->parameters['dynamic_templates'][$name])) {
            throw new InvalidArgumentException("A template named '$name' already exists in '". $ $this->getName() . "'");
        }

        // Ok
        $this->parameters['dynamic_templates'][$name] = $field;

        return $field;
    }

    public function getMapping()
    {
        // Demande à la classe parent de générer le mapping
        $mapping = parent::getMapping();

        // Complète le mapping avec les templates de champs dynamiques
        $templates = [];
        foreach($mapping['dynamic_templates'] as $name => $field) { /* @var Field $field */
            $templates[$name] = [
                'path_match' => $name,
                'mapping' => $field->getMapping()
            ];
        }
        $mapping['dynamic_templates'] = $templates;

        // Ok
        return $mapping;
    }
}
