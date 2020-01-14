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

namespace Docalist\Search\Indexer\Field;

use Docalist\Search\Mapping;
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
    public const FILTER = 'in';

    /**
     * Construit le mapping du champ in.
     *
     * @param Mapping $mapping
     */
    final public static function buildMapping(Mapping $mapping): void
    {
        $mapping
            ->keyword(self::FILTER)
            ->setFeatures(Mapping::FILTER | Mapping::EXCLUSIVE | Mapping::AGGREGATE)
            ->setLabel(__(
                'Filtre sur la collection (le corpus) du document indexé.',
                'docalist-search'
            ))
            ->setDescription(__(
                "Exemples : <code>in:posts</code> (uniquement les articles WordPress),
                <code>in:pages</code> (uniquement les pages WordPress), <code>in:basedoc</code>
                (uniquement les références docalist qui figurent dans la base indiquée).",
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

        $data[self::FILTER] = $collection;
    }
}
