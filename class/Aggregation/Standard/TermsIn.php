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
namespace Docalist\Search\Aggregation\Standard;

use Docalist\Search\Aggregation\Bucket\TermsAggregation;
use Docalist\Search\Indexer;

/**
 * Une agrégation standard de type "terms" sur le champ "in" qui retourne le nombre de documents pour chacune
 * des collections docalist-search indexées.
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
        $this->setTitle(__('Type de contenu', 'docalist-search'));
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