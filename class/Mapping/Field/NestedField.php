<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2020 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Search\Mapping\Field;

use Docalist\Search\Mapping\Field\ObjectField;
use Docalist\Search\Mapping\Options;

/**
 * Un champ objet spécialisé qui créée un sous-document distinct dans l'index de recherche.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/nested.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class NestedField extends ObjectField
{
    /**
     * {@inheritDoc}
     */
    final public function getMapping(Options $options): array
    {
        // Génère le mapping de base
        $mapping = parent::getMapping($options);

        // Type de champ
        $mapping['type'] = 'nested';

        // Ok
        return $mapping;
    }
}
