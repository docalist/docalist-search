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

/**
 * Indexeur pour le champ post_parent.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class PostParentIndexer
{
    /**
     * Nom du champ de recherche.
     *
     * @var string
     */
    public const SEARCH_FIELD = 'parent';

    /**
     * Construit le mapping du champ post_type.
     *
     * @param Mapping $mapping
     */
    final public static function buildMapping(Mapping $mapping): void
    {
        $mapping
            ->keyword(self::SEARCH_FIELD)
            ->setFeatures([Field::AGGREGATE, Field::FILTER, Field::EXCLUSIVE])
            ->setDescription(__(
                "Recherche, facette et filtre sur l'ID du post parent.",
                'docalist-search'
            ));
    }

    /**
     * Indexe les données du champ post_type.
     *
     * @param int   $parent ID du post parent à indexer.
     * @param array $data   Document elasticsearch.
     */
    final public static function buildIndexData(int $parent, array & $data): void
    {
        $data[self::SEARCH_FIELD] = $parent;
    }
}
