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

namespace Docalist\Search\Mapping\Field;

use Docalist\Search\Mapping\Field;
use Docalist\Search\Mapping\Field\Parameter\Fields;
use Docalist\Search\Mapping\Field\Parameter\FieldsTrait;
use Docalist\Search\Mapping\Field\Factory\FieldFactoryTrait;
use Docalist\Search\Mapping\Options;
use InvalidArgumentException;

/**
 * Un champ structuré qui contient d'autres champs.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/object.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ObjectField extends Field implements Fields // pas final, surchargée par NestedField et Mapping
{
    use FieldsTrait, FieldFactoryTrait;

    /**
     * {@inheritDoc}
     */
    final public function mergeWith(Field $other): void
    {
        try {
            parent::mergeWith($other);
            $this->mergeFields($other);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException($other->getName() . '.' . $e->getMessage());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getMapping(Options $options): array // pas final, surchargée par NestedField et Mapping
    {
        // Génère le mapping de base
        $mapping = parent::getMapping($options);

        // Type de champ
        $mapping['type'] = 'object';

        // Le mapping n'est pas dynamique (génère une exception)
        $mapping['dynamic'] = 'strict';

        // Génère le mapping des champs
        $mapping['properties'] = [];
        foreach ($this->fields as $name => $field) {
            $mapping['properties'][$name] = $field->getMapping($options);
        }

        // Ok
        return $mapping;
    }
}
