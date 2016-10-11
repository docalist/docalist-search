<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2013-2016 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search\Aggregation\Bucket;

use Docalist\Search\Aggregation\Bucket\TermsAggregation;

/**
 * Une agrégation de type "terms" qui traduit les termes obtenus en utilisant une taxonomie WordPress.
 */
class TaxonomyEntriesAggregation extends TermsAggregation
{
    /**
     * Taxonomies.
     *
     * @var string[] Un tableau contenant le nom des taxonomies.
     */
    protected $taxonomies;

    /**
     * Constructeur
     *
     * @param string        $field      Champ sur lequel porte l'agrégation.
     * @param string|array  $taxonomies Nom de la ou des taxonomies utilisées pour convertir les termes en libellés
     * @param array         $parameters Autres paramètres de l'agrégation.
     */
    public function __construct($field, $taxonomies, array $parameters = [])
    {
        parent::__construct($field, $parameters);
        $this->setTaxonomies($taxonomies);
        //$this->setTitle($title)
    }

    /**
     * Définit la ou les taxonomies utilisées.
     *
     * @param string|array $taxonomies Nom de la ou des taxonomies utilisées.
     *
     * @eturn self
     */
    public function setTaxonomies($taxonomies)
    {
        $this->taxonomies = (array) $taxonomies;

        return $this;
    }

    /**
     * Retourne les taxonomies utilisées.
     *
     * @return string[] Un tableau contenant les taxonomies utilisées.
     *
     * @eturn self
     */
    public function getTaxonomies()
    {
        return $this->taxonomies;
    }

    public function getBucketLabel($bucket)
    {
        if ($bucket->key === static::MISSING) {
            return $this->getLabelForMissing();
        }

        foreach($this->getTaxonomies() as $taxonomy) {
            $term = get_term_by('slug', $bucket->key, $taxonomy);
            if ($term !== false) {
                return $term->name;
            }
        }

        return $bucket->key;
    }
}
