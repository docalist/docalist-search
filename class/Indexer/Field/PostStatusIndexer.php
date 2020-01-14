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
     * Construit le mapping du champ post_status.
     *
     * @param Mapping $mapping
     */
    final public static function buildMapping(Mapping $mapping): void
    {
        $mapping
            ->text(self::SEARCH_FIELD)
            ->setFeatures(Mapping::FULLTEXT)
            ->setLabel(__(
                'Recherche sur le statut de publication du post WordPress ou de la référence docalist.',
                'docalist-search'
            ))
            ->setDescription(__(
                'Contient à la fois le code et le libellé du statut de publication.
                Exemples : <code>status:publish</code>, <code>status:publié</code>.',
                'docalist-search'
            ));

        $mapping
            ->keyword(self::CODE_FILTER)
            ->setFeatures(Mapping::AGGREGATE | Mapping::FILTER | Mapping::EXCLUSIVE)
            ->setLabel(__(
                'Filtre sur le code du statut de publication du post WordPress ou de la référence docalist.',
                'docalist-search'
            ))
            ->setDescription(__(
                'Contient des codes comme <code>pending</code> ou <code>publish</code>.',
                'docalist-search'
            ));
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

        $data[self::SEARCH_FIELD] = [$status, $label];
        $data[self::CODE_FILTER] = $status;
    }
}
