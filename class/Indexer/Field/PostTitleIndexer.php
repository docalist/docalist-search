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
use Docalist\Tokenizer;
use Transliterator;

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
            ->setFeatures(Mapping::FULLTEXT)
            ->setLabel(__(
                'Recherche sur le titre du post WordPress.',
                'docalist-search'
            ))
            ->setDescription(__(
                "Pour une référence docalist, le titre du post WordPress (posttitle) est en général identique
                au titre de la référence (title). L'attribut supporte la recherche par mot, par troncature et
                par phrase.",
                'docalist-search'
            ));

        $mapping
            ->keyword(self::SORT_FIELD)
            ->setFeatures(Mapping::SORT)
            ->setLabel(__(
                'Tri sur le titre des posts WordPress.',
                'docalist-search'
            ))
            ->setDescription(__(
                'Version en minuscules sans accents ni signes de ponctuation du titre du post.',
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

        $transliterator = Transliterator::createFromRules("::Latin-ASCII; ::Lower; [^[:L:][:N:]]+ > ' ';");
        $sort = $transliterator->transliterate($title);

        $data[static::SEARCH_FIELD] = $title;
        $data[static::SORT_FIELD] = $sort;
    }
}
