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
 * Indexeur pour le champ post_content.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class PostContentIndexer
{
    /**
     * Nom du champ de recherche.
     *
     * @var string
     */
    public const SEARCH_FIELD = 'content';

    /**
     * Construit le mapping du champ post_content.
     *
     * @param Mapping $mapping
     */
    final public static function buildMapping(Mapping $mapping): void
    {
        $mapping
            ->text(self::SEARCH_FIELD)
            ->setFeatures([Field::FULLTEXT])
            ->setDescription(__(
                "Recherche sur les mots du contenu du post.",
                'docalist-search'
            ));
    }

    /**
     * Indexe les données du champ post_content.
     *
     * @param string    $content    Contenu à indexer.
     * @param array     $document   Document elasticsearch.
     */
    final public static function map(string $content, array & $document): void
    {
        !empty($content) && $document[static::SEARCH_FIELD] = $content;
    }
}
