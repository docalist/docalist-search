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
 * Indexeur pour le champ post_excerpt.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class PostExcerptIndexer
{
    /**
     * Nom du champ de recherche.
     *
     * @var string
     */
    public const SEARCH_FIELD = 'excerpt';

    /**
     * Construit le mapping du champ post_excerpt.
     *
     * @param Mapping $mapping
     */
    final public static function buildMapping(Mapping $mapping): void
    {
        $mapping
            ->text(self::SEARCH_FIELD)
            ->setFeatures([Field::FULLTEXT])
            ->setDescription(__(
                "Recherche sur les mots de l'extrait du post.",
                'docalist-search'
            ));
    }

    /**
     * Indexe les données du champ post_excerpt.
     *
     * @param string    $excerpt    Extrait à indexer.
     * @param array     $document   Document elasticsearch.
     */
    final public static function map(string $excerpt, array & $document): void
    {
        !empty($excerpt) && $document[static::SEARCH_FIELD] = $excerpt;
    }
}
