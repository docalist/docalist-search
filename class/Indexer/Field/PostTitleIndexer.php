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
use Docalist\Tokenizer;

/**
 * Indexeur pour le champ post_title.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class PostTitleIndexer
{
    /**
     * Nom du champ de recherche.
     *
     * @var string
     */
    public const SEARCH_FIELD = 'posttitle';

    /**
     * Nom du filtre sur le code.
     *
     * @var string
     */
    public const SORT_FIELD = 'sort.posttitle';

    /**
     * Construit le mapping du champ post_title.
     *
     * @param Mapping $mapping
     */
    final public static function buildMapping(Mapping $mapping): void
    {
        $mapping
            ->text(self::SEARCH_FIELD)
            ->setFeatures([Field::FULLTEXT])
            ->setDescription(__(
                'Recherche sur les mots du titre du post.',
                'docalist-search'
            ));

        $mapping
            ->keyword(self::SORT_FIELD)
            ->setFeatures([Field::SORT])
            ->setDescription(__(
                'Tri par ordre alphabétique sur la version en minuscules sans accents des titres des post.',
                'docalist-search'
            ));
    }

    /**
     * Indexe les données du champ post_title.
     *
     * @param string    $title  Titre à indexer.
     * @param array     $data   Document elasticsearch.
     */
    final public static function buildIndexData(string $title, array & $data): void
    {
        if (empty($title)) {
            return;
        }

        $data[static::SEARCH_FIELD] = $title;
        $data[static::SORT_FIELD] = implode(' ', Tokenizer::tokenize($title));
    }
}
