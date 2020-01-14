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
 * Indexeur pour le champ post_author.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class PostAuthorIndexer
{
    /**
     * Nom du champ de recherche.
     *
     * @var string
     */
    public const SEARCH_FIELD = 'createdby';

    /**
     * Nom du filtre sur le login.
     *
     * @var string
     */
    public const LOGIN_FILTER = 'filter.createdby.login';

    /**
     * Construit le mapping du champ post_author.
     *
     * @param Mapping $mapping
     */
    final public static function buildMapping(Mapping $mapping): void
    {
        $mapping
            ->literal(self::SEARCH_FIELD)
            ->setFeatures(Mapping::FULLTEXT)
            ->setLabel(__(
                "Recherche sur l'utilisateur WordPress qui a créé le post WordPress ou la référence docalist.",
                'docalist-search'
            ))
            ->setDescription(__(
                "Contient l'ID, le login et le nom de l'utilisateur qui a créé le post ou la référence.",
                'docalist-search'
            ));

        $mapping
            ->keyword(self::LOGIN_FILTER)
            ->setFeatures(Mapping::AGGREGATE | Mapping::FILTER | Mapping::EXCLUSIVE)
            ->setLabel(__(
                "Filtre sur le login de l'utilisateur WordPress qui a créé le post WordPress ou
                la référence docalist.",
                'docalist-search'
            ))
            ->setDescription(__(
                "Contient le login de l'utilisateur.",
                'docalist-search'
            ));
    }

    /**
     * Indexe les données du champ post_author.
     *
     * @param int   $userID     ID de l'utilisateur à indexer.
     * @param array $data       Document elasticsearch.
     */
    final public static function buildIndexData(int $userID, array & $data): void
    {
        $user = get_user_by('id', $userID);
        if (empty($user)) {
            return;
        }

        $data[self::SEARCH_FIELD] = [$userID, $user->user_login, $user->display_name];
        $data[self::LOGIN_FILTER] = $user->user_login;
    }
}
