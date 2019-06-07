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

use Docalist\Search\Indexer\Field\PostDateIndexer;
use Docalist\Search\Mapping;
use DateTime;

/**
 * Indexeur pour le champ post_modified.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class PostModifiedIndexer extends PostDateIndexer
{
    /**
     * {@inheritdoc}
     */
    public const SEARCH_FIELD = 'lastupdate';

    /**
     * {@inheritdoc}
     */
    public const DATE_FILTER = 'filter.lastupdate';

    /**
     * {@inheritdoc}
     */
    public const HIERARCHY_FILTER = 'hierarchy.lastupdate';

    /**
     * {@inheritdoc}
     */
    final public static function buildMapping(Mapping $mapping): void // pas final, surchargée par PostModifiedIndexer
    {
        parent::buildMapping($mapping);

        $mapping->getField(static::SEARCH_FIELD)->setLabel(__(
            "Recherche sur la date de dernière modification du post WordPress ou de la référence docalist.",
            'docalist-search'
        ));
        $mapping->getField(static::DATE_FILTER)->setLabel(__(
            "Filtre sur la date de dernière modification du post WordPress ou de la référence docalist.",
            'docalist-search'
        ));
        $mapping->getField(static::HIERARCHY_FILTER)->setLabel(__(
            "Filtre hiérarchique sur la date de dernière modification du post WordPress ou de la référence docalist.",
            'docalist-search'
        ));
    }
}
