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
use stdClass;

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

    public function getBucketLabel(stdClass $bucket)
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
