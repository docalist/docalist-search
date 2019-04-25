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

namespace Docalist\Search;

use Docalist\Search\Mapping\Field\ObjectField;
use Docalist\Search\Mapping\Options;
use Docalist\Search\Mapping\Field\Parameter\Name;

/**
 * Une liste d'attributs de recherche stockés dans l'index elasticsearch.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class Mapping extends ObjectField
{
    /**
     * {@inheritDoc}
     */
    public function getMapping(Options $options): array
    {
        // Génère le mapping de base
        $mapping = parent::getMapping($options);

        // Le mapping lui-même ne doit pas avoir de type
        unset($mapping['type']);

        // Ok
        return $mapping;
    }

    /**
     * Surcharge la méthode mergeName() fournie par NameTrait pour permettre de fusionner deux mappings
     * qui ont des noms différents.
     *
     * @param Name $other
     */
    final protected function mergeName(Name $other): void
    {
        // no-op
    }
}
