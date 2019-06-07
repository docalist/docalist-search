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
 * Indexeur pour le champ post_content.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class PostContentIndexer
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
            ->setFeatures(Mapping::FULLTEXT)
            ->setLabel(__(
                "Recherche sur le contenu des documents : texte de l'article ou de la page
                pour les posts WordPress, description ou résumé pour les références docalist.",
                'docalist-search'
            ))
            ->setDescription(__(
                "Exemples : <code>content:mot</code>, <code>content:mot*</code> (troncature),
                <code>content:\"une expression\"</code> (recherche par phrase en tenant compte
                de l'ordre des mots).",
                'docalist-search'
            ));
    }

    /**
     * Indexe les données du champ post_content.
     *
     * @param string    $content    Contenu à indexer.
     * @param array     $data       Document elasticsearch.
     */
    final public static function buildIndexData(string $content, array & $data): void
    {
        $content = strip_tags($content);
        !empty($content) && $data[static::SEARCH_FIELD] = $content;
    }
}
