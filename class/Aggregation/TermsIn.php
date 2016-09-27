<?php
/**
 * This file is part of the 'SVB Plugin' package.
 *
 * Copyright (C) 2015-2016 Artwaï, Docalist
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search\Aggregation;

use Docalist\Search\Aggregation\Bucket\TermsAggregation;
use Docalist\Search\Indexer;

/**
 * Construit une agrégation de type "terms" sur le champ "collection indexée" (in).
 */
class TermsIn extends TermsAggregation
{
    /**
     * Liste des indexeurs disponibles, indexés par nom de collection ('in').
     *
     * @var Indexer[]
     */
    protected $collections;

    public function __construct()
    {
        parent::__construct('in', ['size' => 1000]);
        $this->setTitle('Type de contenu');
    }

    public function getBucketLabel($bucket)
    {
        // Initialise la liste des collections au premier appel
        if (is_null($this->collections)) {
            $this->collections = docalist('docalist-search-index-manager')->getCollections();
        }

        if (isset($this->collections[$bucket->key])) {
            return $this->collections[$bucket->key]->getLabel();
        }

        return $bucket->key;
    }
}
