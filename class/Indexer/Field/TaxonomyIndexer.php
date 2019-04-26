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
use WP_Taxonomy;
use WP_Term;
use InvalidArgumentException;

/**
 * Indexeur pour les taxonomies associées à un post.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class TaxonomyIndexer
{
    /**
     * Nom du champ de recherche.
     *
     * @var string
     */
    public const SEARCH_FIELD = '{taxonomy}';

    /**
     * Nom du filtre sur le code des termes.
     *
     * @var string
     */
    public const CODE_FILTER = 'filter.{taxonomy}.code';

    /**
     * Nom du filtre sur le libellé des termes.
     *
     * @var string
     */
    public const LABEL_FILTER = 'filter.{taxonomy}.label';

    /**
     * Nom du filtre hiérarchique (uniquement pour les taxonomies hiérarchiques).
     *
     * @var string
     */
    public const HIERARCHY_FILTER = 'filter.{taxonomy}.hierarchy';

    /**
     * Construit le mapping du champ in.
     *
     *
     * @param string        $name       Nom de base des champss de recherche à générer.
     * @param WP_Taxonomy   $taxonomy   Taxonomie à indexer.
     * @param Mapping       $mapping    Mapping à générer.
     */
    final public static function buildMapping(string $name, WP_Taxonomy $taxonomy, Mapping $mapping): void
    {
        $mapping
            ->text(str_replace('{taxonomy}', $name, self::SEARCH_FIELD))
            ->setFeatures([Field::FULLTEXT])
            ->setDescription(sprintf(
                __(
                    'Recherche sur le code ou le libellé des termes de la taxonomie "%s".',
                    'docalist-search'
                ),
                $taxonomy->label
            ));

        $mapping
            ->keyword(str_replace('{taxonomy}', $name, self::CODE_FILTER))
            ->setFeatures([Field::AGGREGATE, Field::FILTER, Field::EXCLUSIVE])
            ->setDescription(sprintf(
                __(
                    'Recherche et filtre sur le code des termes de la taxonomie "%s".',
                    'docalist-search'
                ),
                $taxonomy->label
            ));

        $mapping
            ->keyword(str_replace('{taxonomy}', $name, self::LABEL_FILTER))
            ->setFeatures([Field::AGGREGATE, Field::FILTER, Field::EXCLUSIVE])
            ->setDescription(sprintf(
                __(
                    'Recherche et filtre sur le libellé des termes de la taxonomie "%s".',
                    'docalist-search'
                ),
                $taxonomy->label
            ));

        if ($taxonomy->hierarchical) {
            $mapping
                ->keyword(str_replace('{taxonomy}', $name, self::HIERARCHY_FILTER))
                ->setFeatures([Field::AGGREGATE, Field::FILTER, Field::EXCLUSIVE])
                ->setDescription(sprintf(
                    __(
                        'Facette hiérarchique et filtre sur l\'arborescence des termes de la taxonomie "%s".',
                        'docalist-search'
                    ),
                    $taxonomy->label
                ));
        }
    }

    /**
     * Indexe les données du champ in.
     *
     * @param string        $name       Nom de base des champs de recherche à générer.
     * @param WP_Term[]     $terms      Termes à indexer (tableau d'objets WP_Term).
     * @param WP_Taxonomy   $taxonomy   Taxonomie à laquelle appartiennent les termes.
     * @param array         $document   Document elasticsearch.
     */
    final public static function map(string $name, array $terms, WP_Taxonomy $taxonomy, array & $document): void
    {
        $searchField = str_replace('{taxonomy}', $name, self::SEARCH_FIELD);
        $codeFilter = str_replace('{taxonomy}', $name, self::CODE_FILTER);
        $labelFilter = str_replace('{taxonomy}', $name, self::LABEL_FILTER);
        $hierarchyFilter = $taxonomy->hierarchical ? str_replace('{taxonomy}', $name, self::HIERARCHY_FILTER) : null;

        foreach ($terms as $term) {
            $document[$searchField][] = $term->slug;
            $document[$searchField][] = $term->name;

            $document[$codeFilter][] = $term->slug;
            $document[$labelFilter][] = $term->name;

            $hierarchyFilter && $document[$hierarchyFilter][] = self::getTermPath($term);
        }
    }

    /**
     * Retourne le path d'un terme d'une taxonomie hiérarchique.
     *
     * @param WP_Term $term Terme.
     *
     * @return string
     */
    private static function getTermPath(WP_Term $term): string
    {
        $taxonomy = $term->taxonomy;
        $path = $term->slug;
        foreach (get_ancestors($term->term_id, $taxonomy, 'taxonomy') as $ancestor) {
            $term = get_term($ancestor, $taxonomy);
            $path = $term->slug . '/' . $path;
        }

        return $path;
    }
}
