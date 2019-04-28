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

namespace Docalist\Search\Indexer\Field;

use Docalist\Search\Mapping;
use Docalist\Search\Mapping\Field;
use InvalidArgumentException;

/**
 * Indexeur pour la collection d'un post (champ "in").
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class CollectionIndexer
{
    /**
     * Nom du champ.
     *
     * @var string
     */
    public const SEARCH_FIELD = 'in';

    /**
     * Construit le mapping du champ in.
     *
     * @param Mapping $mapping
     */
    final public static function buildMapping(Mapping $mapping): void
    {
        $mapping
            ->keyword(self::SEARCH_FIELD)
            ->setFeatures([Field::FILTER, Field::EXCLUSIVE])
            ->setDescription(__(
                'Nom de la collection indexée.',
                'docalist-search'
            ));
    }

    /**
     * Indexe les données du champ in.
     *
     * @param string    $collection Nom de la collection à indexer.
     * @param array     $data       Document elasticsearch.
     */
    final public static function buildIndexData(string $collection, array & $data): void
    {
        if (empty($collection)) {
            throw new InvalidArgumentException('Collection is required for the field "in"');
        }

        $data[static::SEARCH_FIELD] = $collection;
    }
}
