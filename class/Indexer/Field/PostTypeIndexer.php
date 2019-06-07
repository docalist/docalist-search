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
            ->setFeatures(Mapping::FULLTEXT)
            ->setLabel(__(
                'Recherche sur le type de post WordPress ou le type de référence docalist.',
                'docalist-search'
            ))
            ->setDescription(__(
                'Contient à la fois le code du type et le libellé associé.
                Exemples : <code>type:post</code>, <code>type:book</code>,
                <code>type:"Blog du site"</code>, <code>type:"Article de périodique"</code>,
                <code>type:org*</code>.',
                'docalist-search'
            ));

        $mapping
            ->keyword(self::CODE_FILTER)
            ->setFeatures(Mapping::AGGREGATE | Mapping::FILTER | Mapping::EXCLUSIVE)
            ->setLabel(__(
                'Filtre sur le code du type de post WordPress ou du type de référence docalist.',
                'docalist-search'
            ))
            ->setDescription(__(
                'Contient des codes comme "post", "page", "book", "article", "person", etc.',
                'docalist-search'
            ));

        $mapping
            ->keyword(self::LABEL_FILTER)
            ->setFeatures(Mapping::AGGREGATE | Mapping::FILTER | Mapping::EXCLUSIVE)
            ->setLabel(__(
                'Filtre sur le libellé du type de post WordPress ou du type de référence docalist.',
                'docalist-search'
            ))
            ->setDescription(__(
                "Pour les références docalist, c'est le libellé indiqué dans la grille de base du type.",
                'docalist-search'
            ));
    }

    /**
     * Indexe les données du champ post_type.
     *
     * @param string    $type   Code du post_type à indexer.
     * @param string    $label  Libellé du post_type à indexer.
     * @param array     $data   Document elasticsearch.
     */
    final public static function buildIndexData(string $code, string $label, array & $data): void
    {
        $data[self::SEARCH_FIELD] = [$code, $label];
        $data[self::CODE_FILTER] = $code;
        $data[self::LABEL_FILTER] = $label;
    }
}
