<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Search\Mapping\Field\Parameter;

use Docalist\Search\Mapping\Field;
use Docalist\Search\Mapping\Field\Parameter\Analyzer;
use Docalist\Search\Mapping\Field\Parameter\Fields;
use Docalist\Search\Mapping\Field\Parameter\Normalizer;
use InvalidArgumentException;

/**
 * Implémentation de l'interface Fields.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
trait FieldsTrait
{
    /**
     * Liste des champs de l'objet.
     *
     * @var Field[]
     */
    private $fields = [];

    /**
     * {@inheritDoc}
     */
    final public function addField(Field $field): Field
    {
        // Récupère le nom du champ à ajouter
        $name = $field->getName();

        // S'il existe déjà un champ avec ce nom, erreur
        if (isset($this->fields[$name])) {
            throw new InvalidArgumentException("A field named '$name' already exists in '". $this->getName() . "'");
        }

        // Ajoute le champ
        $this->fields[$name] = $field;

        // Retourne le champ ajouté
        return $field;
    }

    /**
     * {@inheritDoc}
     */
    final public function hasField(string $name): bool
    {
        return isset($this->fields[$name]);
    }

    /**
     * {@inheritDoc}
     */
    final public function getField(string $name): Field
    {
        if (isset($this->fields[$name])) {
            return $this->fields[$name];
        }

        throw new InvalidArgumentException(sprintf(
            "Field '%s' does not exist in object '%s'",
            $name,
            $this->getName()
        ));
    }

    /**
     * {@inheritDoc}
     */
    final public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * {@inheritDoc}
     */
    final public function getAnalyzers(): array
    {
        $analyzers = [];
        foreach ($this->fields as $field) {
            if ($field instanceof Analyzer) {
                $analyzer = $field->getAnalyzer();
                $analyzers[$analyzer] = $analyzer;
                continue;
            }
            if ($field instanceof Fields) {
                $analyzers += $field->getAnalyzers();
            }
        }

        return $analyzers;
    }

    /**
     * {@inheritDoc}
     */
    final public function getNormalizers(): array
    {
        $normalizers = [];
        foreach ($this->fields as $field) {
            if ($field instanceof Normalizer) {
                $normalizer = $field->getNormalizer();
                $normalizers[$normalizer] = $normalizer;
                continue;
            }
            if ($field instanceof Fields) {
                $normalizers += $field->getNormalizers();
            }
        }

        return $normalizers;
    }

    /**
     * Fusionne avec un autre Fields.
     *
     * @param Fields $other
     */
    final protected function mergeFields(Fields $other): void
    {
        foreach ($other->getFields() as $name => $field) {
            isset($this->fields[$name]) ? $this->fields[$name]->mergeWith($field) : $this->addField($field);
        }
    }
}
