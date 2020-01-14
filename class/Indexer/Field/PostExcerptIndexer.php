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
            ->setFeatures(Mapping::FULLTEXT)
            ->setLabel(__(
                "Recherche sur les mots de l'extrait du post WordPress.",
                'docalist-search'
            ))
            ->setDescription(__(
                "Non utilisé pour les références docalist.",
                'docalist-search'
            ));
    }

    /**
     * Indexe les données du champ post_excerpt.
     *
     * @param string    $excerpt    Extrait à indexer.
     * @param array     $data       Document elasticsearch.
     */
    final public static function buildIndexData(string $excerpt, array & $data): void
    {
        !empty($excerpt) && $data[static::SEARCH_FIELD] = $excerpt;
    }
}
