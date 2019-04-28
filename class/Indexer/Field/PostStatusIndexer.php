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
 * Indexeur pour le champ post_status.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class PostStatusIndexer
{
    /**
     * Nom du champ de recherche.
     *
     * @var string
     */
    public const SEARCH_FIELD = 'status';

    /**
     * Nom du filtre sur le code.
     *
     * @var string
     */
    public const CODE_FILTER = 'filter.status.code';

    /**
     * Nom du filtre sur le libellé.
     *
     * @var string
     */
    public const LABEL_FILTER = 'filter.status.label';

    /**
     * Construit le mapping du champ post_status.
     *
     * @param Mapping $mapping
     */
    final public static function buildMapping(Mapping $mapping): void
    {
        $mapping
            ->text(self::SEARCH_FIELD)
            ->setFeatures([Field::FULLTEXT])
            ->setDescription(__(
                'Recherche sur le code ou le libellé du statut de publication.',
                'docalist-search'
            ));

        $mapping
            ->keyword(self::CODE_FILTER)
            ->setFeatures([Field::AGGREGATE, Field::FILTER, Field::EXCLUSIVE])
            ->setDescription(__(
                'Facette et filtre sur le code du statut de publication.',
                'docalist-search'
            ))
            ->copyTo(self::SEARCH_FIELD);

        $mapping
            ->keyword(self::LABEL_FILTER)
            ->setFeatures([Field::AGGREGATE, Field::FILTER, Field::EXCLUSIVE])
            ->setLabel(__(
                'Facette et filtre sur le libellé du statut de publication.',
                'docalist-search'
            ))
            ->copyTo(self::SEARCH_FIELD);
    }

    /**
     * Indexe les données du champ post_status.
     *
     * @param string    $status Code du post_status à indexer.
     * @param array     $data   Document elasticsearch.
     */
    final public static function buildIndexData(string $status, array & $data): void
    {
        $object = get_post_status_object($status);
        $label = empty($status) ? $status : $object->label;

        // SEARCH_FIELD : via copy_to
        $data[self::CODE_FILTER] = $status;
        $data[self::LABEL_FILTER] = $label;
    }
}
