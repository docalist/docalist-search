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

namespace Docalist\Search\Aggregation\Bucket;

use Docalist\Search\Aggregation\Bucket\TermsAggregation;
use stdClass;

/**
 * Une agrégation de type "terms" qui traduit les termes obtenus en utilisant une taxonomie WordPress.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class TaxonomyEntriesAggregation extends TermsAggregation
{
    /**
     * Taxonomies.
     *
     * @var string[] Un tableau contenant le nom des taxonomies.
     */
    private $taxonomies;

    /**
     * Constructeur
     *
     * @param string        $field          Champ sur lequel porte l'agrégation.
     * @param string|array  $taxonomies     Taxonomie(s) utilisée(s) pour convertir les termes en libellés.
     * @param array         $parameters     Autres paramètres de l'agrégation.
     * @param array         $options        Options d'affichage.
     */
    public function __construct($field, $taxonomies, array $parameters = [], array $options = [])
    {
        parent::__construct($field, $parameters, $options);
        $this->setTaxonomies($taxonomies);
    }

    /**
     * Définit la ou les taxonomies utilisées.
     *
     * @param string|array $taxonomies Nom de la ou des taxonomies utilisées.
     */
    final public function setTaxonomies($taxonomies): void
    {
        $this->taxonomies = (array) $taxonomies;
    }

    /**
     * Retourne les taxonomies utilisées.
     *
     * @return string[] Un tableau contenant les taxonomies utilisées.
     *
     * @eturn self
     */
    final public function getTaxonomies(): array
    {
        return $this->taxonomies;
    }

    /**
     * {@inheritDoc}
     */
    final public function getBucketLabel(stdClass $bucket): string
    {
        if ($bucket->key === static::MISSING) {
            return $this->getMissingLabel();
        }

        foreach ($this->getTaxonomies() as $taxonomy) {
            $term = get_term_by('slug', $bucket->key, $taxonomy);
            if ($term !== false) {
                return $term->name;
            }
        }

        return $bucket->key;
    }
}
