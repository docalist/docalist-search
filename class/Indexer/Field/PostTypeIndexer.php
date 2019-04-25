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
 * Indexeur pour le champ post_type.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class PostTypeIndexer
{
    /**
     * Nom du champ de recherche.
     *
     * @var string
     */
    public const SEARCH_FIELD = 'type';

    /**
     * Nom du filtre sur le code.
     *
     * @var string
     */
    public const CODE_FILTER = 'filter.type.code';

    /**
     * Nom du filtre sur le libellé.
     *
     * @var string
     */
    public const LABEL_FILTER = 'filter.type.label';

    /**
     * Construit le mapping du champ post_type.
     *
     * @param Mapping $mapping
     */
    final public static function buildMapping(Mapping $mapping): void
    {
        $mapping
            ->text(self::SEARCH_FIELD)
            ->setFeatures([Field::FULLTEXT])
            ->setDescription(__(
                'Recherche sur le code ou le libellé du type de post.',
                'docalist-search'
            ));

        $mapping
            ->keyword(self::CODE_FILTER)
            ->setFeatures([Field::AGGREGATE, Field::FILTER, Field::EXCLUSIVE])
            ->setDescription(__(
                'Facette et filtre sur le code du type de publication.',
                'docalist-search'
            ));

        $mapping
            ->keyword(self::LABEL_FILTER)
            ->setFeatures([Field::AGGREGATE, Field::FILTER, Field::EXCLUSIVE])
            ->setLabel(__(
                'Facette et filtre sur le libellé du type de publication.',
                'docalist-search'
            ));
    }

    /**
     * Indexe les données du champ post_type.
     *
     * @param string    $type       Code du post_type à indexer.
     * @param string    $label      Libellé du post_type à indexer.
     * @param array     $document   Document elasticsearch.
     */
    final public static function map(string $code, string $label, array & $document): void
    {
        $document[self::SEARCH_FIELD] = [$code, $label];
        $document[self::CODE_FILTER] = $code;
        $document[self::LABEL_FILTER] = $label;
    }
}
