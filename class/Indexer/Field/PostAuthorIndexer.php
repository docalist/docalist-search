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
     * Nom du filtre sur l'ID.
     *
     * @var string
     */
    public const ID_FILTER = 'filter.createdby.id';

    /**
     * Nom du filtre sur le login.
     *
     * @var string
     */
    public const LOGIN_FILTER = 'filter.createdby.login';

    /**
     * Nom du filtre sur le nom.
     *
     * @var string
     */
    public const NAME_FILTER = 'filter.createdby.name';

    /**
     * Construit le mapping du champ post_author.
     *
     * @param Mapping $mapping
     */
    final public static function buildMapping(Mapping $mapping): void
    {
        $mapping
            ->text(self::SEARCH_FIELD)
            ->setFeatures([Field::FULLTEXT])
            ->setDescription(__(
                "Recherche sur l'ID, le login ou le nom de l'utilisateur qui a créé le post.",
                'docalist-search'
            ));

        $mapping
            ->keyword(self::ID_FILTER)
            ->setFeatures([Field::AGGREGATE, Field::FILTER, Field::EXCLUSIVE])
            ->setDescription(__(
                "Facette et filtre sur l'ID de l'utilisateur qui a créé le post.",
                'docalist-search'
            ))
            ->copyTo(self::SEARCH_FIELD);

        $mapping
            ->keyword(self::LOGIN_FILTER)
            ->setFeatures([Field::AGGREGATE, Field::FILTER, Field::EXCLUSIVE])
            ->setDescription(__(
                "Facette et filtre sur le login de l'utilisateur qui a créé le post.",
                'docalist-search'
            ))
            ->copyTo(self::SEARCH_FIELD);

        $mapping
            ->keyword(self::NAME_FILTER)
            ->setFeatures([Field::AGGREGATE, Field::FILTER, Field::EXCLUSIVE])
            ->setDescription(__(
                "Facette et filtre sur le nom de l'utilisateur qui a créé le post.",
                'docalist-search'
            ))
            ->copyTo(self::SEARCH_FIELD);
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

        // SEARCH_FIELD : via copy_to
        $data[self::ID_FILTER] = $userID;
        $data[self::LOGIN_FILTER] = $user->user_login;
        $data[self::NAME_FILTER] = $user->display_name;
    }
}
