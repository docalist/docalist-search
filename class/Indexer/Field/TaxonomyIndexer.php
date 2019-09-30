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
use WP_Taxonomy;
use WP_Term;

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
    public const SEARCH_FIELD = 'taxonomy-{taxonomy}';

    /**
     * Nom du filtre sur le code des termes.
     *
     * @var string
     */
    public const CODE_FILTER = 'filter.taxonomy-{taxonomy}.code';

    /**
     * Nom du filtre hiérarchique (uniquement pour les taxonomies hiérarchiques).
     *
     * @var string
     */
    public const HIERARCHY_FILTER = 'hierarchy.taxonomy-{taxonomy}';

    /**
     * Retourne le nom du champ de recherche pour la taxonomie indiquée.
     *
     * @param string $taxonomy
     *
     * @return string
     */
    public static function searchField(string $taxonomy): string
    {
        return str_replace('{taxonomy}', $taxonomy, self::SEARCH_FIELD);
    }

    /**
     * Retourne le nom du filtre sur le code pour la taxonomie indiquée.
     *
     * @param string $taxonomy
     *
     * @return string
     */
    public static function codeFilter(string $taxonomy): string
    {
        return str_replace('{taxonomy}', $taxonomy, self::CODE_FILTER);
    }

    /**
     * Retourne le nom du filtre hiérarchique pour la taxonomie indiquée.
     *
     * @param string $taxonomy
     *
     * @return string
     */
    public static function hierarchyFilter(string $taxonomy): string
    {
        return str_replace('{taxonomy}', $taxonomy, self::HIERARCHY_FILTER);
    }

    /**
     * Construit le mapping du champ in.
     *
     * @param WP_Taxonomy   $taxonomy   Taxonomie à indexer.
     * @param Mapping       $mapping    Mapping à générer.
     */
    final public static function buildMapping(WP_Taxonomy $taxonomy, Mapping $mapping): void
    {
        $mapping
            ->text(self::searchField($taxonomy->name))
            ->setFeatures(Mapping::FULLTEXT)
            ->setLabel(sprintf(
                __(
                    'Recherche sur les termes de la taxonomie WordPress "%s".',
                    'docalist-search'
                ),
                $taxonomy->label
            ))
            ->setDescription(sprintf(
                __(
                    'Contient le code (le slug) et le libellé des termes de la taxonomie "%s" qui ont été
                    attribués au post WordPress.',
                    'docalist-search'
                ),
                $taxonomy->label
            ));

        $mapping
            ->keyword(self::codeFilter($taxonomy->name))
            ->setFeatures(Mapping::AGGREGATE | Mapping::FILTER)
            ->setLabel(sprintf(
                __(
                    'Filtre sur les termes de la taxonomie WordPress "%s".',
                    'docalist-search'
                ),
                $taxonomy->label
            ))
            ->setDescription(sprintf(
                __(
                    'Contient le slug des termes de la taxonomie "%s" qui ont été attribués au post WordPress.',
                    'docalist-search'
                ),
                $taxonomy->label
            ));

        if ($taxonomy->hierarchical) {
            $mapping
                ->hierarchy(self::hierarchyFilter($taxonomy->name))
                ->setFeatures(Mapping::AGGREGATE | Mapping::FILTER)
                ->setLabel(sprintf(
                    __(
                        'Filtre hiérarchique sur l\'arborescence des termes de la taxonomie WordPress "%s".',
                        'docalist-search'
                    ),
                    $taxonomy->label
                ))
                ->setDescription(sprintf(
                    __(
                        'Contient un path de la forme "niveau1/niveau2/slug" qui fournit l\'arborescence
                        compléte de chacun des termes qui ont été attribués au post WordPress.
                        Permet de créer une facette hiérarchique (par niveau) sur la taxonomie WordPress "%s".',
                        'docalist-search'
                    ),
                    $taxonomy->label
                ));
        }
    }

    /**
     * Indexe les données du champ in.
     *
     * @param WP_Term[]     $terms      Termes à indexer (tableau d'objets WP_Term).
     * @param WP_Taxonomy   $taxonomy   Taxonomie à laquelle appartiennent les termes.
     * @param array         $data       Document elasticsearch.
     */
    final public static function buildIndexData(array $terms, WP_Taxonomy $taxonomy, array & $data): void
    {
        $searchField = self::searchField($taxonomy->name);
        $codeFilter = self::codeFilter($taxonomy->name);
        $hierarchyFilter = $taxonomy->hierarchical ? self::hierarchyFilter($taxonomy->name) : null;

        foreach ($terms as $term) {
            $data[$searchField][] = $term->slug;
            $data[$searchField][] = $term->name;
            $data[$codeFilter][] = $term->slug;

            $hierarchyFilter && $data[$hierarchyFilter][] = self::getTermPath($term);
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
